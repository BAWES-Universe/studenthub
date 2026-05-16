from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def read(path):
    return (ROOT / path).read_text(encoding="utf-8")


def require(condition, message):
    if not condition:
        raise SystemExit(message)


extractor = read("common/components/IdExpiryDateExtractor.php")
cron = read("console/controllers/CronController.php")
config = read("common/config/main.php")
docs = read("docs/textract-civil-id-extractor.md")

require("AWS_TEXTRACT_ACCESS_KEY_ID" in config, "Textract access key must stay env-backed.")
require("AWS_TEXTRACT_SECRET_ACCESS_KEY" in config, "Textract secret key must stay env-backed.")

require("configurationError" in extractor, "Extractor must fail closed when config is missing.")
require("isSafeDocumentName" in extractor, "Extractor must validate S3 object names before Textract reads.")
require("str_starts_with($documentName, 'photos/')" in extractor, "Extractor must restrict reads to Civil ID photo keys.")
require("$e->getMessage()" not in extractor, "Extractor must not return raw AWS/provider exception messages.")
require("Unable to read document text." in extractor, "Extractor must return a sanitized OCR failure message.")

require('$response[\'operation\'] == "success" && !empty($response[\'matches\'])' in cron, "Cron must only update expiry dates when OCR returns date matches.")
require(": time()" not in cron, "Cron must not default missing OCR dates to the current time.")

require("Only `photos/` S3 object keys" in docs, "Textract hardening docs must describe the S3 key boundary.")

print("Textract Civil ID extractor hardening check passed.")
