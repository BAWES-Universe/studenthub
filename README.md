# StudentHub — Yii2 Backend API

> **Bounty contributors: read this entire README before starting any work.** This repository is one part of a larger ecosystem. Understanding the full picture will save you hours.

StudentHub is a platform that manages corporate recruitment and internship programs — connecting **candidates** (students), **companies** (employers), **staff** (admin operators), **inspectors**, and **managers** through a structured placement, time-tracking, and payment system.

---

## 🗺️ Ecosystem Map — All Related Repos

This Yii2 backend **serves APIs** consumed by multiple frontend applications. Before working on any issue, understand which frontend it affects:

| Repository | Purpose | Tech Stack | Status |
|---|---|---|---|
| **[BAWES-Universe/studenthub](https://github.com/BAWES-Universe/studenthub)** | ⭐ **This repo** — REST API backend | Yii2 (PHP 8.2), MySQL, Docker | ✅ Production |
| **[BAWES-Universe/studenthub-angular](https://github.com/BAWES-Universe/studenthub-angular)** | Candidate & Company frontend apps | Angular + Ionic | ✅ Production (reference implementation) |
| **[BAWES-Universe/studenthub-admin](https://github.com/BAWES-Universe/studenthub-admin)** | Staff admin panel | Angular | ✅ Production |
| **[BAWES-Universe/studenthub-codex](https://github.com/BAWES-Universe/studenthub-codex)** | New frontend rebuild | Next.js 15, TypeScript, Prisma | 🚧 In development |
| **[BAWES-Universe/studenthub-landing](https://github.com/BAWES-Universe/studenthub-landing)** | Marketing / landing pages | Static / Next.js | ✅ Live |

> **Note on other BAWES projects:** [Plugn](https://github.com/BAWES-Universe/plugn) is a separate BAWES product currently offline pending dev availability. [Universe](https://github.com/BAWES-Universe/universe) is another active open source project. Both are independent of StudentHub.

---

## 🖥️ Frontend Apps — Two Candidate App Generations

### Generation 1: Angular + Ionic (Production ✅)

The [`studenthub-angular`](https://github.com/BAWES-Universe/studenthub-angular) repo is the **battle-tested, production frontend**. It contains:
- Candidate app (job search, applications, time tracking)
- Company app (job posting, candidate management, payment)
- All screens fully implemented and represent the intended UX

**Before building any new feature in the Next.js codex, check the Angular app first.** The Angular app is the source of truth for business logic and UI flows this API must support.

### Generation 2: Next.js / Codex (🚧 In Development)

The [`studenthub-codex`](https://github.com/BAWES-Universe/studenthub-codex) repo is the modern rebuild:
- Built with Next.js 15 (App Router), TypeScript, Tailwind CSS, Prisma ORM
- **Not yet live** — bounties on this repo are greenfield work
- Replaces the Angular app long-term; consult Angular screens as the reference design

### Admin Panel

The [`studenthub-admin`](https://github.com/BAWES-Universe/studenthub-admin) Angular app powers the staff operations panel.

- **Local dev:** `http://localhost:8888/bawes/studenthub/admin/web/`
- **Production:** `https://admin.studenthub.com.kw` *(staff access only)*

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND LAYER                       │
│                                                         │
│  studenthub-angular     studenthub-codex                │
│  (Angular/Ionic)        (Next.js 15) 🚧                  │
│  Candidate + Company    Candidate + Company (rebuild)   │
│                                                         │
│  studenthub-admin       studenthub-landing              │
│  (Angular)              (Static/Next.js)                │
│  Staff Admin Panel      Marketing Site                  │
└─────────────┬───────────────────────┬───────────────────┘
              │    REST API calls      │
              ▼                       ▼
┌─────────────────────────────────────────────────────────┐
│              THIS REPO — Yii2 Backend API               │
│                                                         │
│  /candidate  → Candidate-facing API (port 22080)        │
│  /company    → Company-facing API   (port 23080)        │
│  /admin      → Admin panel API      (port 21080)        │
│  /staff      → Staff operations     (port 25080)        │
│  /inspector  → Inspector app        (port 24080)        │
│  /manager    → Manager tools                            │
│  /verification → ID verification    (port 26080)        │
│  /console    → CLI / migrations                         │
│  /cron       → Scheduled background jobs                │
│  /common     → Shared models, components, services      │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
              ┌───────────────────┐
              │   MySQL Database   │
              │  (Docker managed)  │
              └───────────────────┘
```

---

## 📁 Backend Module Structure

Each directory is a **separate Yii2 application** that shares the `common/` module:

| Directory | Local Port | Who consumes it |
|---|---|---|
| `admin/` | 21080 | Internal admin panel + Gii code generator |
| `candidate/` | 22080 | Candidate mobile/web app |
| `company/` | 23080 | Company/employer app |
| `inspector/` | 24080 | Field inspector app |
| `staff/` | 25080 | Internal staff operations |
| `verification/` | 26080 | Identity verification flow |
| `manager/` | — | Manager-facing features |
| `console/` | — | CLI commands, DB migrations |
| `cron/` | — | Scheduled background tasks |
| `status/` | — | Health check endpoint |
| `common/` | — | Shared models, components, services |

---

## 🚀 Getting Started (Local Development)

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed
- [Git](https://git-scm.com/) installed
- No local PHP or Composer required — everything runs inside Docker

### 1. Clone the repo

```bash
git clone git@github.com:BAWES-Universe/studenthub.git
cd studenthub
```

### 2. Start the local environment

```bash
docker-compose -f docker-compose-local.yml -p studenthub-local-server up -d
```

### 3. Verify APIs are running

| App | Local URL |
|---|---|
| Admin | http://localhost:21080 |
| Candidate | http://localhost:22080 |
| Company | http://localhost:23080 |
| Inspector | http://localhost:24080 |
| Staff | http://localhost:25080 |
| Verification | http://localhost:26080 |
| Gii (code generator) | http://localhost:8888/bawes/studenthub/admin/web/gii |

### 4. Run database migrations

```bash
docker exec -it studenthub-backend-local ./yii migrate
```

### 5. Access the backend container shell

```bash
docker exec -it studenthub-backend-local /bin/bash
```

---

## 🧩 Docker Compose Environments

| File | Use case |
|---|---|
| `docker-compose-local.yml` | **Local development** — use this for bounty work |
| `docker-compose-dev.yml` | Remote dev server / CI |
| `docker-compose-prod.yml` | Production deployment |

---

## 🧪 Running Tests

```bash
./run-tests.sh
```

Or inside the container:
```bash
docker exec -it studenthub-backend-local ./vendor/bin/codecept run
```

---

## 🔧 Common Dev Commands

### Cache management

```bash
# Flush schema cache (always run after migrations if models don't reflect new columns)
docker exec -it studenthub-backend-local ./yii cache/flush-schema db
docker exec -it studenthub-backend-local ./yii cache/flush cache

# Nuclear option — wipe all runtime caches
docker exec -it studenthub-backend-local bash -c "
  rm -rf /var/www/html/admin/runtime/cache \
         /var/www/html/candidate/runtime/cache \
         /var/www/html/company/runtime/cache \
         /var/www/html/staff/runtime/cache \
         /var/www/html/inspector/runtime/cache \
         /var/www/html/manager/runtime/cache \
         /var/www/html/console/runtime/cache \
         /var/www/html/common/runtime/cache
"
```

### MySQL

```bash
docker-compose exec mysql mysql -u root -p
docker-compose exec mysql mysql -u studenthubuser -pstudenthub -h mysql-1
```

### DNS (macOS — if local API domains don't resolve)

```bash
# Add to /etc/hosts: 127.0.0.1 student.api.studenthub.co
sudo vim /etc/hosts
sudo killall -HUP mDNSResponder
```

---

## 🐳 Docker Reference

### Cross-platform build (required for Apple Silicon → Linux server deployments)

```bash
docker buildx build --platform linux/amd64 -t studenthub/backend-dev -f Dockerfile-nginx-dev .
docker buildx build --platform linux/amd64 -t studenthub/backend-prod -f Dockerfile-nginx-prod .
```

### Push to AWS ECR

```bash
# Authenticate
aws ecr get-login-password --region eu-west-2 | docker login --username AWS --password-stdin 438663597141.dkr.ecr.eu-west-2.amazonaws.com

# Dev image
docker tag studenthub/backend-dev:latest 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-dev:latest
docker push 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-dev:latest

# Prod image
docker tag studenthub/backend-prod:latest 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest
docker push 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest
```

---

## 📚 Documentation

| Doc | Description |
|---|---|
| [Setup Guide](docs/setup.md) | Full installation and configuration |
| [User Roles](docs/user-roles.md) | User types and permissions |
| [API Endpoints](docs/api-endpoints.md) | All available API endpoints |
| [Database](docs/database/README.md) | Schema and ER diagrams |
| [Cron Jobs](docs/cron-jobs.md) | Scheduled background tasks |
| [Analytics](docs/analytics.md) | Event tracking |

---

## 🏆 Bounty Contributor Checklist

Before opening a PR for a bounty issue:

- [ ] **Read the issue fully** — note which module it targets (`candidate/`, `company/`, `staff/`, etc.)
- [ ] **Check the Angular app** ([studenthub-angular](https://github.com/BAWES-Universe/studenthub-angular)) to understand the existing UX flow this API supports
- [ ] **Check Codex** ([studenthub-codex](https://github.com/BAWES-Universe/studenthub-codex)) if the issue is for new endpoints powering the Next.js rebuild
- [ ] **Run migrations** after schema changes: `./yii migrate`
- [ ] **Flush schema cache** if ActiveRecord errors appear after migrations
- [ ] **Test with `docker-compose-local.yml`** for all local development
- [ ] **Do not break existing API contracts** — the Angular app is in production and depends on current endpoint behaviour
- [ ] **Write or update tests** for new or modified endpoints

---

## 🔑 Server SSH Agent Setup (Ubuntu)

Add to `~/.bashrc` or `~/.profile`:

```bash
if [ -z "$SSH_AUTH_SOCK" ]; then
    eval "$(ssh-agent -s)"
    ssh-add ~/.ssh/github
fi
```

---

## 📦 S3 Utilities

```bash
# Export DB snapshot to S3
aws s3 cp ./db.sql s3://studenthub-uploads-dev-server/exports/db.sql
```

---

*This project is open source under the [MIT License](LICENSE.md). All work happens in public — contributions welcome via GitHub Issues and Pull Requests.*
