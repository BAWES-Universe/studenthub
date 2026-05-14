# StudentHub — Yii2 Backend API

> **Bounty contributors: read this entire README before starting any work.** This repository is one part of a larger ecosystem. Understanding the full picture will save you hours and prevent you from breaking production.

StudentHub is a platform that manages corporate recruitment and internship programs — connecting **candidates** (students), **companies** (employers), **staff** (admin operators), **inspectors**, and **managers** through a structured placement, time-tracking, and payment system.

---

## 🗺️ Ecosystem Map — All Related Repos

This Yii2 backend **serves APIs** consumed by multiple frontend applications. Before working on any issue, understand which frontend it affects:

| Repository | Purpose | Tech Stack | Status |
|---|---|---|---|
| **[BAWES-Universe/studenthub](https://github.com/BAWES-Universe/studenthub)** | ⭐ **This repo** — REST API backend | Yii2 (PHP 8.2), MySQL, Docker | ✅ Production |
| **[BAWES-Universe/studenthub-angular](https://github.com/BAWES-Universe/studenthub-angular)** | Candidate & Company frontend apps | Angular 17 + Ionic 7 | ✅ Production (reference) |
| **[BAWES-Universe/studenthub-admin](https://github.com/BAWES-Universe/studenthub-admin)** | Staff admin panel | Angular 17 | ✅ Production |
| **[BAWES-Universe/studenthub-landing](https://github.com/BAWES-Universe/studenthub-landing)** | Marketing / landing pages | Next.js | ✅ Live |
| **[BAWES-Universe/studenthub-codex](https://github.com/BAWES-Universe/studenthub-codex)** | Unified frontend + backend rebuild | Next.js 15, TypeScript, Prisma, Tailwind | 🚧 In development |

> **Codex** is our long-term effort to unify the frontend and backend into a single modern codebase. It is **not yet in production**. All active bounties affecting live users target this repo (the Yii2 API) and the Angular frontends.

---

## 🖥️ Frontend Apps — Two Generations

### Generation 1: Angular + Ionic (Production ✅)

**Repo:** [`studenthub-angular`](https://github.com/BAWES-Universe/studenthub-angular)
**Stack:** Angular 17, Ionic 7, TypeScript, SCSS
**Runs as:** Hybrid mobile/web app (iOS, Android, browser)

This is the **battle-tested production frontend**, fully implemented and live for real users. It contains:

- **Candidate app** — job search, applications, time tracking, payments
- **Company app** — job posting, candidate management, approval flows

> ⚠️ **Before working on any API endpoint, check the Angular app first.** It is the source of truth for what business logic and response shapes this API must support. Breaking changes here affect real users immediately.

### Admin Panel: Angular (Production ✅)

**Repo:** [`studenthub-admin`](https://github.com/BAWES-Universe/studenthub-admin)
**Stack:** Angular 17, TypeScript, SCSS
**Local dev:** `http://localhost:8888/bawes/studenthub/admin/web/`

Powers all internal staff operations — candidate management, company approvals, payment processing, reporting.

### Generation 2: Codex — Next.js Unified Rebuild (🚧 In Development)

**Repo:** [`studenthub-codex`](https://github.com/BAWES-Universe/studenthub-codex)
**Stack:** Next.js 15 (App Router), TypeScript, Tailwind CSS, Prisma ORM, tRPC

Codex is the long-term replacement for both the Angular frontends and this Yii2 backend — a single unified codebase. It is **not yet live**. Bounty work on Codex is greenfield; use the Angular apps as your reference for intended UX and data models.

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND LAYER                       │
│                                                         │
│  studenthub-angular          studenthub-admin           │
│  Angular 17 + Ionic 7        Angular 17                 │
│  Candidate + Company apps    Staff Operations Panel     │
│  ✅ Production               ✅ Production              │
│                                                         │
│  studenthub-landing          studenthub-codex           │
│  Next.js — Marketing site    Next.js 15 — Unified       │
│  ✅ Live                     🚧 In development          │
└─────────────┬───────────────────────┬───────────────────┘
              │    REST API calls      │
              ▼                       ▼
┌─────────────────────────────────────────────────────────┐
│              THIS REPO — Yii2 Backend API               │
│                                                         │
│  /candidate    → Candidate-facing API   (port 22080)    │
│  /company      → Company-facing API     (port 23080)    │
│  /admin        → Admin panel API        (port 21080)    │
│  /staff        → Staff operations       (port 25080)    │
│  /inspector    → Inspector app          (port 24080)    │
│  /manager      → Manager tools                          │
│  /verification → Identity verification (port 26080)    │
│  /console      → CLI / DB migrations                    │
│  /cron         → Scheduled background jobs              │
│  /common       → Shared models, services, components   │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
              ┌───────────────────┐
              │   MySQL Database  │
              │  (Docker managed) │
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

## 🐳 Deployment (Self-Hosting / Open Source)

StudentHub is fully open source. You can self-host the entire platform.

### Cross-platform build (required for Apple Silicon → Linux server)

```bash
docker buildx build --platform linux/amd64 -t studenthub/backend-dev -f Dockerfile-nginx-dev .
docker buildx build --platform linux/amd64 -t studenthub/backend-prod -f Dockerfile-nginx-prod .
```

### Deploy

```bash
# Start production stack
docker-compose -f docker-compose-prod.yml -p studenthub-prod-server up -d

# Start dev stack
docker-compose -f docker-compose-dev.yml -p studenthub-dev-server up -d
```

> For cloud deployments (AWS ECS, ECR, etc.), configure your own container registry and update the image tags accordingly. See your infrastructure team or the `docker-compose-prod.yml` file for the full configuration.

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
- [ ] **Study the Angular app** ([studenthub-angular](https://github.com/BAWES-Universe/studenthub-angular)) to understand the existing UX flow and API response shapes your change must support
- [ ] **Check Codex** ([studenthub-codex](https://github.com/BAWES-Universe/studenthub-codex)) if the issue mentions new endpoints for the Next.js rebuild
- [ ] **Run migrations** after any schema changes: `./yii migrate`
- [ ] **Flush schema cache** if ActiveRecord errors appear after migrations
- [ ] **Use `docker-compose-local.yml`** for all local development
- [ ] **Do not break existing API contracts** — the Angular app is live and depends on current endpoint behaviour
- [ ] **Write or update tests** for new or modified endpoints
- [ ] **Never commit credentials, secrets, or production config** to this repo

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

*This project is open source. All work happens in public — contributions welcome via GitHub Issues and Pull Requests on [github.com/BAWES-Universe](https://github.com/BAWES-Universe).*
