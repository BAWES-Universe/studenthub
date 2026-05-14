# StudentHub — Payroll Platform for the Youth Training Program

StudentHub is a multi-tenant payroll and recruitment platform connecting **companies**, **trainees (candidates)**, **staff**, **inspectors**, and **managers** through a structured youth training programme. It handles trainee placement, time tracking, invoice generation, and payment processing.

> **This is the core backend API.** Before you start work — especially if you arrived via a bounty — read the [Ecosystem Map](#-ecosystem-map) below so you understand how this repo fits into the wider platform.

---

## 🗺 Ecosystem Map

StudentHub is made up of several repositories across the [BAWES-Universe](https://github.com/BAWES-Universe) GitHub organisation. A change in this backend repo may affect any of the frontends listed below.

### Core Backend (this repo)

| Repo | Language | Purpose |
|------|----------|---------|
| [studenthub](https://github.com/BAWES-Universe/studenthub) ⬅ **you are here** | PHP / Yii2 | REST API — all business logic, DB migrations, cron jobs |

### Frontend Apps

| Repo | Language | Who uses it |
|------|----------|-------------|
| [studenthub-candidate](https://github.com/BAWES-Universe/studenthub-candidate) | TypeScript / Ionic | Trainees — view pay, submit hours, upload Civil ID |
| [studenthub-candidate-react](https://github.com/BAWES-Universe/studenthub-candidate-react) | TypeScript / Ionic React | Newer React rebuild of the candidate app |
| [studenthub-candidate-next](https://github.com/BAWES-Universe/studenthub-candidate-next) | TypeScript / Next.js | Next.js rebuild (in progress) |
| [studenthub-company](https://github.com/BAWES-Universe/studenthub-company) | TypeScript / Ionic | Companies — log hours, generate invoices, make payments |
| [studenthub-staff](https://github.com/BAWES-Universe/studenthub-staff) | TypeScript / Ionic | Internal staff — manage candidates & companies |

### Shared Infrastructure & Related Projects

| Repo | Language | Purpose |
|------|----------|---------|
| [wallet](https://github.com/BAWES-Universe/wallet) | PHP | BAWES wallet service (used for payments) |
| [BAWES-ERP](https://github.com/BAWES-Universe/BAWES-ERP) | TypeScript | Internal ERP covering all BAWES entities |
| [BAWES-ERP-sdk](https://github.com/BAWES-Universe/BAWES-ERP-sdk) | TypeScript | SDK for ERP API integration |
| [BAWES-ERP-frontend](https://github.com/BAWES-Universe/BAWES-ERP-frontend) | TypeScript | ERP frontend dashboard |
| [plugn](https://github.com/BAWES-Universe/plugn) | PHP | Plugin marketplace platform (currently offline — seeking contributors) |

### Universe Platform (virtual office / community)

| Repo | Language | Purpose |
|------|----------|---------|
| [workadventure-universe-admin](https://github.com/BAWES-Universe/workadventure-universe-admin) | TypeScript | Admin panel for the Universe virtual office |
| [universe-maps](https://github.com/BAWES-Universe/universe-maps) | HTML | Map assets for WorkAdventure |
| [universe-matrix-synapse](https://github.com/BAWES-Universe/universe-matrix-synapse) | Shell | Matrix/Synapse chat server config for Universe |

---

## 🏗 Architecture Overview

This backend exposes **7 separate API modules**, each running on its own port in development:

| Module | Dev Port | Who calls it |
|--------|----------|--------------|
| Admin | `21080` | Internal admins |
| Candidate | `22080` | Candidate frontend apps |
| Company | `23080` | Company frontend app |
| Inspector | `24080` | Inspector app |
| Staff | `25080` | Staff app |
| Manager | `26080` | Manager interface |
| Verification | `27080` | Document verification flow |

**Tech stack:** PHP 8.2 · Yii2 · MySQL · AWS S3 · AWS SES · AWS SQS · AWS MediaConvert · Docker · Railway (production hosting)

---

## ⚡ Quick Start (Local Development)

### Prerequisites
- Docker Desktop
- AWS CLI (for S3/SES operations)
- Composer (PHP dependency manager)

### 1. Clone and configure environment

```bash
git clone https://github.com/BAWES-Universe/studenthub.git
cd studenthub
cp .env.example .env   # fill in your environment variables (see below)
```

### 2. Required environment variables

Never commit credentials. All secrets must come from environment variables:

```env
# Database
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=

# AWS S3 — Temp upload bucket
AWS_TEMP_BUCKET_KEY=
AWS_TEMP_BUCKET_SECRET=
AWS_TEMP_BUCKET_REGION=
AWS_TEMP_BUCKET_NAME=

# AWS S3 — Permanent bucket
AWS_PERMANENT_S3_ACCESS_KEY_ID=
AWS_PERMANENT_S3_SECRET_ACCESS_KEY=
AWS_PERMANENT_S3_REGION=
AWS_PERMANENT_S3_BUCKET=

# AWS SES mailer
MAIL_HOST=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_PORT=

# AWS SQS (EventManager)
AWS_SQS_KEY=
AWS_SQS_SECRET=
AWS_SQS_REGION=
AWS_SQS_QUEUE_URL=

# AWS MediaConvert
AWS_MEDIACONVERT_ACCESS_KEY_ID=
AWS_MEDIACONVERT_SECRET_ACCESS_KEY=
```

### 3. Start the development server

```bash
docker-compose -f docker-compose-local.yml -p studenthub-local-server up -d
```

All 7 API modules will be available at their respective ports (see Architecture table above).

### 4. Run database migrations

```bash
docker exec -it studenthub-backend-local ./yii migrate
```

### 5. Run tests

```bash
./run-tests.sh
```

---

## 🔧 Common Developer Commands

### Docker

```bash
# Enter the backend container
docker exec -it studenthub-backend-local /bin/bash

# View logs
docker logs studenthub-backend-local

# Rebuild after Dockerfile changes
docker-compose -f docker-compose-local.yml up --build

# Flush schema cache after migrations
./yii cache/flush-schema db
./yii cache/flush cache
```

### Environment-specific startup

```bash
# Local
docker-compose -f docker-compose-local.yml -p studenthub-local-server up -d

# Dev server
docker-compose -f docker-compose-dev.yml -p studenthub-dev-server up -d

# Production
docker-compose -f docker-compose-prod.yml -p studenthub-prod-server up -d
```

### AWS ECR — Build and Push

```bash
# Login
aws ecr get-login-password --region eu-west-2 | docker login --username AWS --password-stdin 438663597141.dkr.ecr.eu-west-2.amazonaws.com

# Cross-platform build (required for Linux/amd64 deployment)
docker buildx build --platform linux/amd64 -t studenthub/backend-dev -f Dockerfile-nginx-dev .
docker buildx build --platform linux/amd64 -t studenthub/backend-prod -f Dockerfile-nginx-prod .

# Tag and push
docker tag studenthub/backend-prod:latest 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest
docker push 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest
```

---

## 💰 Contributing via Bounties

This project uses [Algora](https://algora.io) for paid open-source contributions. If you arrived here from a bounty issue, read this section carefully.

### Before you write a single line of code

1. **Read the full issue** — bounty issues list specific phases. Only claim the phase you intend to complete.
2. **Check open PRs** — someone may already be working on your phase. Check [open pull requests](https://github.com/BAWES-Universe/studenthub/pulls) before starting.
3. **Understand the ecosystem** — this is the backend only. Frontend apps live in separate repos (see [Ecosystem Map](#-ecosystem-map)). A backend API change may break a frontend — note it in your PR.
4. **One PR per phase** — do not bundle multiple phases into one PR. Smaller, focused PRs are reviewed and merged faster.

### Claiming a phase

Comment `/claim #<issue-number>` on the issue to signal your intent. Only one contributor is awarded the bounty per phase.

### PR checklist before submitting

- [ ] Your PR title references the issue number (e.g. `Fix Civil ID S3 path — closes #55 Phase 2`)
- [ ] You have run `git diff --check` (no whitespace errors)
- [ ] No credentials, secrets, or `.env` values are committed
- [ ] PHP changes have been linted (`php -l` or Docker equivalent)
- [ ] Your PR description explains what changed, why, and how you verified it
- [ ] CodeRabbit will auto-review your PR — address its comments before requesting merge

### Security rules (mandatory for all contributors)

- **Never commit AWS keys, SMTP passwords, or any credentials** — use `getenv()` with `.env` files
- **Never hardcode bucket names or queue URLs** — these must be environment variables
- If you discover leaked credentials in history, report it in the issue — do not attempt rotation yourself

---

## 📚 Documentation

Detailed documentation lives in the [`docs/`](docs/) directory:

| File | Contents |
|------|----------|
| [docs/setup.md](docs/setup.md) | Full installation and configuration guide |
| [docs/user-roles.md](docs/user-roles.md) | User types and permission levels |
| [docs/api-endpoints.md](docs/api-endpoints.md) | Available API endpoints by module |
| [docs/database/README.md](docs/database/README.md) | Database structure and ER diagrams |
| [docs/cron-jobs.md](docs/cron-jobs.md) | Scheduled background tasks |
| [docs/analytics.md](docs/analytics.md) | Event tracking and analytics |

---

## 🐛 Troubleshooting

**ActiveRecord / Table column not found after migration:**
```bash
docker exec -it studenthub-backend-local ./yii cache/flush-schema db
# If still broken, clear all runtime caches:
rm -rf /var/www/html/*/runtime/cache
```

**MySQL access from Docker to local host:**
```sql
GRANT ALL PRIVILEGES ON *.* TO 'root'@'192.168.1.5' IDENTIFIED BY 'root' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON wallet.* TO 'studenthubuser'@'127.0.0.1';
```

**DNS / local API routing:**
```bash
sudo vim /etc/hosts
# Add: 127.0.0.1 student.api.studenthub.co
sudo killall -HUP mDNSResponder
```

**SSH agent on Ubuntu server:**
```bash
# Add to ~/.bashrc
if [ -z "$SSH_AUTH_SOCK" ]; then
  eval "$(ssh-agent -s)"
  ssh-add ~/.ssh/github
fi
```

---

## 🏷 Versioning

```bash
git tag -a v2.0 -m "Version 2.0: PHP 7.4 to 8.2"
git push origin v2.0
git tag -l
```

---

## 📄 Licence

Open source — see [LICENSE](LICENSE) for details.
