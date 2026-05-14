# StudentHub
### Payroll Platform for Kuwait's Youth Training Programme

[![Open Source](https://img.shields.io/badge/open%20source-yes-brightgreen)](https://github.com/BAWES-Universe/studenthub)
[![Bounties](https://img.shields.io/badge/bounties-algora.io-blue)](https://algora.io)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4)](https://php.net)
[![Yii2](https://img.shields.io/badge/framework-Yii2-blue)](https://www.yiiframework.com)

---

## What is StudentHub?

StudentHub is a government-aligned payroll and workforce management platform built specifically for Kuwait’s youth training programme. It connects **companies** that hire trainees with the **trainees (candidates)** themselves, managed through a team of **staff**, **inspectors**, and **administrators**.

The platform handles the full employment lifecycle: recruitment, document verification (Civil ID), timesheet submission, invoice generation, and direct salary transfers to trainees’ bank accounts.

### By the numbers

| Metric | Value |
|--------|-------|
| 💸 Annual trainee salary disbursements | ~**KWD 5 million** |
| 📅 Years in active production | **9+ years** (since 2017) |
| 🏗 Built by | [BAWES](https://bawes.net) — a Kuwait-based software company |
| 🔓 Licence | Open source |

This platform is **live, processing real payroll** for real people. Your code matters.

---

## About BAWES Universe

StudentHub is one of several open-source products built and maintained by **BAWES** under the [BAWES-Universe](https://github.com/BAWES-Universe) GitHub organisation.

Other notable products in the ecosystem:

- **[Plugn](https://github.com/BAWES-Universe/plugn)** — A plugin/app marketplace platform for online stores. Processed millions in transactions with 8,000+ stores registered across 14 countries. Currently seeking contributors to bring it back online.
- **[Universe](https://github.com/BAWES-Universe/workadventure-universe-admin)** — A virtual office and community space built on WorkAdventure, used by the BAWES team and open-source community.
- **[BAWES ERP](https://github.com/BAWES-Universe/BAWES-ERP)** — Internal ERP covering all BAWES business entities.

Each product has its own repositories and onboarding. This README focuses entirely on getting you productive on **StudentHub**.

---

## StudentHub Repository Map

StudentHub is split across multiple repositories. **This repo is the backend API — the source of truth for all business logic, database schema, and data.** All frontend apps depend on it.

| Repo | Stack | Purpose |
|------|-------|---------|
| ⬅ **[studenthub](https://github.com/BAWES-Universe/studenthub)** | PHP 8.2 / Yii2 | Core backend REST API, DB migrations, cron jobs, business logic |
| [studenthub-candidate](https://github.com/BAWES-Universe/studenthub-candidate) | TypeScript / Ionic (Angular) | Trainee app — view pay, upload Civil ID, submit hours |
| [studenthub-candidate-react](https://github.com/BAWES-Universe/studenthub-candidate-react) | TypeScript / Ionic React | React rebuild of the candidate app |
| [studenthub-candidate-next](https://github.com/BAWES-Universe/studenthub-candidate-next) | TypeScript / Next.js | Next.js rebuild (in progress) |
| [studenthub-company](https://github.com/BAWES-Universe/studenthub-company) | TypeScript / Ionic | Company portal — log hours, generate invoices, make payments |
| [studenthub-staff](https://github.com/BAWES-Universe/studenthub-staff) | TypeScript / Ionic | Staff app — manage candidates and company assignments |

> **Before you start:** If your bounty involves an API endpoint, a database field, or a file upload flow — check whether a frontend repo also needs updating. Note it in your PR.

---

## Architecture

The backend is a **Yii2 advanced application** structured as multiple independent API modules, each serving a different user type. In development, each module runs on its own port via Docker.

| Module | Dev Port | Serves |
|--------|----------|--------|
| `admin` | `21080` | BAWES administrators |
| `candidate` | `22080` | Trainees / candidates |
| `company` | `23080` | Employer companies |
| `inspector` | `24080` | Compliance inspectors |
| `staff` | `25080` | Internal staff |
| `manager` | `26080` | Programme managers |
| `verification` | `27080` | Document verification |

### Tech stack

| Layer | Technology |
|-------|------------|
| Language | PHP 8.2 |
| Framework | Yii2 (advanced template) |
| Database | MySQL |
| File storage | AWS S3 (temp + permanent buckets) |
| Email | AWS SES (SMTP) |
| Queue | AWS SQS (EventManager) |
| Video processing | AWS MediaConvert |
| Search | Algolia |
| PDF generation | Puppeteer (headless Chrome) |
| Hosting | Railway (production), AWS ECR (container registry) |
| CI/CD | GitHub Actions |

### User roles

| Role | Description |
|------|-------------|
| **Candidate** | Trainee enrolled in the programme. Submits timesheets, uploads Civil ID and bank details, receives salary. |
| **Company** | Employer. Creates monthly transfer requests with trainee hours, verifies them, receives receipts. |
| **Staff** | BAWES employees. Create and manage candidate accounts, assign trainees to companies. |
| **Inspector** | Compliance officers. Verify documentation, audit company records, flag issues. |
| **Admin** | Full system access. Approve transfers, process payments, send receipts, generate reports. |
| **Manager** | Programme oversight (role in development). |

---

## Local Development Setup

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop)
- [AWS CLI](https://aws.amazon.com/cli/) (for S3/SES operations)
- PHP 8.2 + Composer (for running lint/tests outside Docker)

### 1. Clone the repository

```bash
git clone https://github.com/BAWES-Universe/studenthub.git
cd studenthub
```

### 2. Configure environment variables

Copy the example env file and fill in your values. **Never commit real credentials.**

```bash
cp .env.example .env
```

Required environment variables:

```env
# Database
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=

# AWS S3 — Temporary upload bucket
AWS_TEMP_BUCKET_KEY=
AWS_TEMP_BUCKET_SECRET=
AWS_TEMP_BUCKET_REGION=eu-west-2
AWS_TEMP_BUCKET_NAME=

# AWS S3 — Permanent bucket
AWS_PERMANENT_S3_ACCESS_KEY_ID=
AWS_PERMANENT_S3_SECRET_ACCESS_KEY=
AWS_PERMANENT_S3_REGION=eu-west-2
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

### 3. Start the local server

```bash
docker-compose -f docker-compose-local.yml -p studenthub-local-server up -d
```

All 7 API modules will be accessible at their respective ports immediately.

### 4. Run database migrations

```bash
docker exec -it studenthub-backend-local ./yii migrate
```

### 5. Run tests

```bash
./run-tests.sh
```

### 6. Access the code generator (Gii)

```bash
open http://localhost:21080/gii
```

---

## Common Commands

### Container access & logs

```bash
# Enter the running backend container
docker exec -it studenthub-backend-local /bin/bash

# View logs
docker logs studenthub-backend-local

# Rebuild after Dockerfile changes
docker-compose -f docker-compose-local.yml up --build
```

### Database

```bash
# Access MySQL
docker-compose exec mysql mysql -u studenthubuser -pstudenthub -h mysql-1

# Flush schema cache after migrations (required when columns aren’t detected)
docker exec -it studenthub-backend-local ./yii cache/flush-schema db
docker exec -it studenthub-backend-local ./yii cache/flush cache

# If cache flush doesn’t help, clear runtime directories manually
rm -rf /var/www/html/*/runtime/cache
```

### Cron jobs (manual trigger)

```bash
docker exec -it studenthub-backend-local ./yii cron/update-candidate-stats
docker exec -it studenthub-backend-local ./yii cron/update-company-stats
```

### Algolia search index

```bash
cd console && ../yii algolia/index fulltimer
cd console && ../yii algolia/index candidate
```

### Copy DB export to S3

```bash
aws s3 cp ./db.sql s3://studenthub-uploads-dev-server/exports/db.sql
```

---

## Deployment

### Environment startup

```bash
# Dev server
docker-compose -f docker-compose-dev.yml -p studenthub-dev-server up -d

# Production
docker-compose -f docker-compose-prod.yml -p studenthub-prod-server up -d
```

### Build and push to AWS ECR

```bash
# Authenticate
aws ecr get-login-password --region eu-west-2 \
  | docker login --username AWS --password-stdin \
    438663597141.dkr.ecr.eu-west-2.amazonaws.com

# Build for Linux/amd64 (required — do not skip the platform flag)
docker buildx build --platform linux/amd64 \
  -t studenthub/backend-prod \
  -f Dockerfile-nginx-prod .

# Tag and push
docker tag studenthub/backend-prod:latest \
  438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest

docker push \
  438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest
```

### Tagging a release

```bash
git tag -a v2.1 -m "Version 2.1: description"
git push origin v2.1
```

---

## 💰 Bounty Contributor Guide

This project offers paid contributions via [Algora](https://algora.io). Welcome — and thank you for contributing to a platform that pays real salaries to real students.

### Read before you write a single line of code

**1. Read the entire issue.** Bounty issues are broken into numbered phases. Each phase is a separate, focused slice of work. Only claim the phase you intend to complete in full.

**2. Check for duplicate PRs.** Open the [Pull Requests tab](https://github.com/BAWES-Universe/studenthub/pulls) and check if someone already has a PR open for your phase. If they do, don’t submit a duplicate — look for an unclaimed phase instead.

**3. This is a live production system.** The platform processes salary payments. Security vulnerabilities and data bugs have real-world consequences. Be careful, be thorough.

**4. This is the backend only.** Frontend apps live in separate repos (see [StudentHub Repository Map](#studenthub-repository-map)). If your change alters an API response shape, a field name, or a file upload path — mention in your PR which frontend repos are affected.

**5. CodeRabbit reviews every PR automatically.** Address its comments before asking for a human review. PRs with unaddressed CodeRabbit feedback will not be merged.

### Claiming a phase

Comment `/claim #<issue-number>` on the issue. Example: `/claim #55`

One contributor is awarded the bounty per phase. First to submit a passing, reviewable PR for that phase wins.

### PR checklist

- [ ] PR title references the issue and phase (e.g. `Fix Civil ID S3 path — closes #55 Phase 2`)
- [ ] `git diff --check` passes (no trailing whitespace)
- [ ] No credentials, `.env` values, or AWS keys are committed
- [ ] PHP files pass lint: `php -l path/to/file.php` (or Docker equivalent)
- [ ] PR description explains: what changed, why, how you verified it
- [ ] CodeRabbit comments addressed
- [ ] If your change affects an API response, frontend repos are noted

### Security rules — mandatory

- ❌ Never commit AWS access keys, SMTP passwords, bucket names, or queue URLs
- ✅ Use `getenv('VAR_NAME') ?: 'safe-default'` in PHP config files
- ✅ Document any new env vars in this README under the environment variables section
- If you find exposed credentials in the codebase, **report in the issue** — do not rotate keys yourself

---

## Documentation

| File | Contents |
|------|----------|
| [docs/setup.md](docs/setup.md) | Full server setup including Puppeteer and PHP extensions |
| [docs/user-roles.md](docs/user-roles.md) | Full breakdown of user types and permissions |
| [docs/api-endpoints.md](docs/api-endpoints.md) | Available API endpoints per module |
| [docs/database/README.md](docs/database/README.md) | Database schema and ER diagrams |
| [docs/cron-jobs.md](docs/cron-jobs.md) | Scheduled background tasks and their triggers |
| [docs/analytics.md](docs/analytics.md) | Event tracking and analytics implementation |

---

## Troubleshooting

**Columns not found after running a migration:**
```bash
docker exec -it studenthub-backend-local ./yii cache/flush-schema db
# Still broken? Clear all runtime cache:
rm -rf /var/www/html/*/runtime/cache
```

**MySQL grant access from Docker to local host:**
```sql
GRANT ALL PRIVILEGES ON *.* TO 'root'@'192.168.1.5' IDENTIFIED BY 'root' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON wallet.* TO 'studenthubuser'@'127.0.0.1';
```

**Local API domain not resolving:**
```bash
sudo vim /etc/hosts
# Add: 127.0.0.1 student.api.studenthub.co
sudo killall -HUP mDNSResponder
# Use http:// (not https://) for local testing
```

**SSH agent not persisting on server reboot:**
```bash
# Add to ~/.bashrc on the Ubuntu server
if [ -z "$SSH_AUTH_SOCK" ]; then
  eval "$(ssh-agent -s)"
  ssh-add ~/.ssh/github
fi
```

**Server reboot — remember to restart the right environment:**
```bash
# Production
docker-compose -f docker-compose-prod.yml -p studenthub-prod-server up -d

# Dev server
docker-compose -f docker-compose-dev.yml -p studenthub-dev-server up -d
```

---

## Licence

Open source. See [LICENSE](LICENSE) for details.

---

*Built by [BAWES](https://bawes.net) · [BAWES-Universe on GitHub](https://github.com/BAWES-Universe)*
