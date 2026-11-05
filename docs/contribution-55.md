# 🔐 AWS S3 Security Remediation & Civil ID Upload Fix  
**Project:** AWS S3 Security Remediation & Civil ID Upload Fix — IAM Key Rotation, Bucket Hardening & Backend Patches  
**Client:** @mau16ro (Algora bounty – $600 USD)  

---  

## 1️⃣ Resumen Ejecutivo  

| **Objetivo** | **Resultado esperado** |
|--------------|------------------------|
| **Hardening** del bucket S3 que almacena documentos de identificación civil. | Políticas de bucket restrictivas, cifrado *at‑rest* y *in‑flight*, bloqueo de versiones y registro de auditoría. |
| **Rotación automática** de las claves de acceso (Access Key / Secret Key) de los usuarios IAM que interactúan con el bucket. | Script/cron que detecta claves próximas a caducar, genera nuevas, actualiza los recursos y revoca la clave anterior sin tiempo de inactividad. |
| **Parches backend** para la subida de la *Civil ID* (documento de identificación). | Validación de tipo MIME, escaneo de virus con ClamAV, sanitización de nombre de archivo, uso de URL firmada (presigned URL) y manejo de errores robusto. |
| **Entregables** | Código listo para producción (Python 3.11), pruebas unitarias (pytest), scripts de rotación (AWS CLI/Boto3), documentación de despliegue (Terraform + Docker). |

---

## 2️⃣ Arquitectura de la Solución  

```mermaid
graph TD
    subgraph "Frontend"
        FE[SPA (React/Vue) / Mobile]
        FE -->|GET presigned URL| API[API Gateway]
    end

    subgraph "Backend"
        API -->|Invoke| Lambda[Lambda (Python) – Upload Handler]
        Lambda -->|AssumeRole| STS[STS – Temp Credentials]
        STS -->|Temporary keys| S3[S3 Bucket (civil-id-store)]
        Lambda -->|Log| CloudWatch[CloudWatch Logs]
    end

    subgraph "Security"
        IAM[IAM User / Role] -->|Rotación de claves| Rotator[Rotator (Python script + CloudWatch Events)]
        Rotator -->|Update| IAM
        IAM -->|Policy| BucketPolicy[Bucket Policy (Least‑privilege)]
        S3 -->|ObjectLock + Versioning| Hardening[Hardening Controls]
    end

    subgraph "CI/CD"
        CI[GitHub Actions] -->|Build & Test| Docker[Docker Image (lambda-layer)]
        Docker -->|Deploy| Lambda
        CI -->|Run| PyTest[pytest suite]
    end
```

### Componentes Clave  

| Componente | Tecnologías | Responsabilidades |
|------------|-------------|-------------------|
| **S3 Bucket** | ‑ Versioning <br>‑ Object Lock (Governance) <br>‑ Server‑Side Encryption (SSE‑S3) <br>‑ Block Public Access | Almacena los documentos de forma inmutable y cifrada. |
| **Bucket Policy** | IAM policy JSON | Solo el *role* `arn:aws:iam::<ACCOUNT_ID>:role/CivilIdUploadRole` puede *PUT* y *GET* objetos; denegación explícita de *DELETE*. |
| **IAM Role** | `CivilIdUploadRole` (assume by Lambda) | Permite `s3:PutObject`, `s3:GetObject`, `s3:ListBucket` en el bucket. |
| **Lambda (Upload Handler)** | Python 3.11, `boto3`, `python-magic`, `clamav` (layer) | 1️⃣ Valida MIME y tamaño. <br>2️⃣ Escanea con ClamAV. <br>3️⃣ Genera URL firmada (5 min). <br>4️⃣ Registra auditoría. |
| **Rotator Script** | Python 3.11, `boto3`, `schedule` | Detecta claves > 30 días a expirar, crea una nueva, actualiza políticas y elimina la antigua. Se ejecuta vía **EventBridge** (cron). |
| **IaC** | Terraform (v1.6) | Creación del bucket, políticas, roles, Lambda y EventBridge. |
| **CI/CD** | GitHub Actions | Lint → Unit tests → Docker image → Terraform apply (dev). |

---

## 3️⃣ Código Fuente  

> **Nota:** Todos los valores sensibles (ARN, IDs, nombres de bucket) están parametrizados mediante variables de entorno o *terraform variables*.  
> **Requisitos:** Python ≥ 3.11, Docker, Terraform ≥ 1.6, AWS CLI ≥ 2.13  

### 3.1 `terraform/main.tf`

```hcl
terraform {
  required_version = ">= 1.6"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
  backend "s3" {
    bucket = var.tfstate_bucket
    key    = "s3-remediation/terraform.tfstate"
    region = var.aws_region
  }
}

provider "aws" {
  region = var.aws_region
}

# --------------------------------------------------------------
# S3 Bucket (hardening)
# --------------------------------------------------------------
resource "aws_s3_bucket" "civil_id_bucket" {
  bucket = var.civil_id_bucket_name

  force_destroy = false

  versioning {
    enabled = true
  }

  server_side_encryption_configuration {
    rule {
      apply_server_side_encryption_by_default {
        sse_algorithm = "AES256"
      }
    }
  }

  object_lock_configuration {
    object_lock_enabled = "Enabled"
    rule {
      default_retention {
        mode  = "GOVERNANCE"
        days  = 365
      }
    }
  }

  # Block all public access
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# --------------------------------------------------------------
# IAM Role for Lambda
# --------------------------------------------------------------
resource "aws_iam_role" "lambda_role" {
  name = "CivilIdUploadRole"

  assume_role_policy = data.aws_iam_policy_document.lambda_assume.json
}

data "aws_iam_policy_document" "lambda_assume" {
  statement {
    actions = ["sts:AssumeRole"]
    principals {
      type        = "Service"
      identifiers = ["lambda.amazonaws.com"]
    }
  }
}

resource "aws_iam_policy" "s3_upload_policy" {
  name   = "CivilIdS3UploadPolicy"
  policy = data.aws_iam_policy_document.s3_upload.json
}

data "aws_iam_policy_document" "s3_upload" {
  statement {
    effect    = "Allow"
    actions   = ["s3:PutObject", "s3:GetObject", "s3:ListBucket"]
    resources = [
      "${aws_s3_bucket.civil_id_bucket.arn}",
      "${aws_s3_bucket.civil_id_bucket.arn}/*"
    ]
  }
}

resource "aws_iam_role_policy_attachment" "attach_s3_policy" {
  role       = aws_iam_role.lambda_role.name
  policy_arn = aws_iam_policy.s3_upload_policy.arn
}

# --------------------------------------------------------------
# Lambda (Upload Handler)
# --------------------------------------------------------------
resource "aws_lambda_function" "upload_handler" {
  function_name = "civil-id-upload-handler"
  role          = aws_iam_role.lambda_role.arn
  handler       = "handler.lambda_handler"
  runtime       = "python3.11"
  timeout       = 30
  memory_size   = 256

  filename         = data.archive_file.lambda_zip.output_path
  source_code_hash = data.archive_file.lambda_zip.output_base64sha256

  environment {
    variables = {
      BUCKET_NAME           = aws_s3_bucket.civil_id_bucket.id
      MAX_FILE_SIZE_MB      = "10"
      ALLOWED_MIME_TYPES    = "application/pdf,image/jpeg,image/png"
      CLAMAV_DEFINITIONS    = "/opt/clamav/defs"
    }
  }

  # Layer with ClamAV binaries (pre‑built)
  layers = [aws_lambda_layer_version.clamav_layer.arn]
}

data "archive_file" "lambda_zip" {
  type        = "zip"
  source_dir  = "${path.module}/lambda"
  output_path = "${path.module}/lambda_package.zip"
}

resource "aws_lambda_layer_version" "clamav_layer" {
  layer_name          = "clamav-layer"
  compatible_runtimes = ["python3.11"]
  filename            = "${path.module}/layers/clamav.zip"
  description         = "ClamAV binaries + definitions"
}
```

> **Variables (`variables.tf`)** – define `aws_region`, `civil_id_bucket_name`, `tfstate_bucket`, etc.

### 3.2 Lambda Handler (`lambda/handler.py`)

```python
# -*- coding: utf-8 -*-
"""
Lambda handler for secure Civil ID uploads.

- Validates MIME type & file size.
- Scans file with ClamAV (via Lambda layer).
- Generates a presigned PUT URL (5 min expiry).
- Logs operation to CloudWatch (structured JSON).
"""

import os
import json
import base64
import logging
import boto3
import magic  # python-magic
import subprocess
from datetime import datetime, timezone

# ----------------------------------------------------------------------
# Configuration (environment variables)
# ----------------------------------------------------------------------
BUCKET_NAME = os.getenv("BUCKET_NAME")
MAX_FILE_SIZE_MB = int(os.getenv("MAX_FILE_SIZE_MB", "10"))
ALLOWED_MIME_TYPES = set(
    mime.strip() for mime in os.getenv("ALLOWED_MIME_TYPES", "").split(",")
)
CLAMAV_DEFINITIONS = os.getenv("CLAMAV_DEFINITIONS", "/opt/clamav/defs")
PRESIGN_EXPIRY = 300  # seconds (5 min)

# ----------------------------------------------------------------------
# Logging – JSON for CloudWatch Insights
# ----------------------------------------------------------------------
logger = logging.getLogger()
logger.setLevel(logging.INFO)


def _log(event: str, **kwargs):
    payload = {"event": event, "timestamp": datetime.now(timezone.utc).isoformat()}
    payload.update(kwargs)
    logger.info(json.dumps(payload))


# ----------------------------------------------------------------------
# Helper: Validate MIME
# ----------------------------------------------------------------------
def _detect_mime(content: bytes) -> str:
    mime = magic.from_buffer(content, mime=True)
    return mime


# ----------------------------------------------------------------------
# Helper: Virus Scan (ClamAV)
# ----------------------------------------------------------------------
def _scan_with_clamav(content: bytes) -> bool:
    """
    Returns True if the file is clean.
    Raises RuntimeError if ClamAV exits with an error.
    """
    # Write to temporary file (in /tmp – Lambda writeable)
    temp_path = "/tmp/upload.bin"
    with open(temp_path, "wb") as f:
        f.write(content)

    cmd = [
        "/opt/bin/clamdscan",
        "--fdpass",
        "--no-summary",
        "--quiet",
        "--detect-pua",
        temp_path,
    ]
    result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    # clamdscan returns 0 = clean, 1 = infected, >1 = error
    if result.returncode == 0:
        return True
    elif result.returncode == 1:
        _log("virus_detected", file=temp_path)
        return False
    else:
        raise RuntimeError(f"ClamAV error: {result.stderr.decode()}")


# ----------------------------------------------------------------------
# Core Lambda handler
# ----------------------------------------------------------------------
def lambda_handler(event: dict, context):
    """
    Expected payload (JSON):
    {
        "filename": "my_id.pdf",
        "content": "<base64‑encoded file>"
    }
    Returns:
        200 – {"presigned_url": "..."}   (if all checks pass)
        400 – error details
    """
    try:
        body = json.loads(event.get("body", "{}"))
        filename = body["filename"]
        b64_content = body["content"]
    except (KeyError, json.JSONDecodeError) as exc:
        _log("invalid_input", error=str(exc))
        return _response(400, {"error": "Invalid request payload."})

    # ------------------------------------------------------------------
    # 1️⃣ Decode & size validation
    # ------------------------------------------------------------------
    try:
        file_bytes = base64.b64decode(b64_content, validate=True)
    except Exception as exc:
        _log("b64_decode_error", error=str(exc))
        return _response(400, {"error": "Invalid base64 content."})

    size_mb = len(file_bytes) / (1024 * 1024)
    if size_mb > MAX_FILE_SIZE_MB:
        _log("size_exceeded", size_mb=size_mb, limit=MAX_FILE_SIZE_MB)
        return _response(400, {"error": f"File exceeds {MAX_FILE_SIZE_MB} MiB limit."})

    # ------------------------------------------------------------------
    # 2️⃣ MIME type validation
    # ------------------------------------------------------------------
    mime_type = _detect_mime(file_bytes)
    if mime_type not in ALLOWED_MIME_TYPES:
        _log(
            "mime_not_allowed",
            filename=filename,
            mime=mime_type,
            allowed=list(ALLOWED_MIME_TYPES),
        )
        return _response(400, {"error": f"MIME type {mime_type} not allowed."})

    # ------------------------------------------------------------------
    # 3️⃣ Virus scan
    # ------------------------------------------------------------------
    try:
        clean = _scan_with_clamav(file_bytes)
        if not clean:
            return _response(400, {"error": "File is infected with a virus."