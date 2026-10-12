# StudentHub — Backend API (Yii2)

> **Payroll & Recruitment Platform for the Youth Training Program**
> Connects trainees (candidates), companies, staff supervisors, inspectors, managers, and admins through structured placement, time-tracking, and payment workflows.

---

## ⚠️ Bounty Contributors — Read This First

This repo is the **Yii2 PHP backend**. It powers **14 interconnected repositories**. Before starting any bounty:

1. Read the [Ecosystem Map](#ecosystem-map) below — understand which app your issue belongs to
2. Find the matching frontend repo and read *its* README
3. Check the [API app inside this repo](#backend-apps-in-this-repo) that your feature touches
4. If building a UI screen, study the **Angular reference implementation** before writing new code — see [Frontend Tech Stack](#frontend-tech-stack)

---

## Ecosystem Map

The full StudentHub platform is split across multiple repos. This backend serves all of them.

### 🔵 This Repo — PHP Backend (Yii2)
| Internal App | Local Port | API Base URL (dev) | Purpose |
|---|---|---|---|
| `admin/` | 21080 | `admin.api.dev.studenthub.co` | Internal admin panel API |
| `candidate/` | 22080 | `candidate.api.dev.studenthub.co` | Trainee/candidate API |
| `company/` | 23080 | `company.api.dev.studenthub.co` | Employer/company API |
| `inspector/` | 24080 | `inspector.api.dev.studenthub.co` | On-site inspector API |
| `staff/` | 25080 | `staff.api.dev.studenthub.co` | Internal staff operations API |
| `verification/` | 26080 | `verification.api.dev.studenthub.co` | Document verification API |
| `manager/` | — | `manager.api.dev.studenthub.co` | Manager reporting API |
| `status/` | — | — | System health/status |
| `console/` | — | CLI only | Console commands & migrations |
| `cron/` | — | CLI only | Scheduled background jobs |

### 🟢 Frontend Repos (Angular / Ionic — Production)

These are the **battle-tested production apps**. Study these before building anything new — they contain the established UX patterns, API usage, and business logic that works in production.

| Repo | Stack | Who uses it | Status |
|---|---|---|---|
| [studenthub-candidate](https://github.com/BAWES-Universe/studenthub-candidate) | **Angular + Ionic** | Trainees / candidates | ✅ Production reference |
| [studenthub-company](https://github.com/BAWES-Universe/studenthub-company) | **Angular + Ionic** | Employers | ✅ Production reference |
| [studenthub-staff](https://github.com/BAWES-Universe/studenthub-staff) | **Angular + Ionic** | Internal staff | ✅ Production reference |
| [studenthub-inspector](https://github.com/BAWES-Universe/studenthub-inspector) | **Angular + Ionic** | On-site inspectors | ✅ Production reference |
| [studenthub-admin](https://github.com/BAWES-Universe/studenthub-admin) | **Angular** | Admins | ✅ Production reference |
| [studenthub-manager](https://github.com/BAWES-Universe/studenthub-manager) | **Angular** | Managers | ✅ Production reference |
| [studenthub-finance](https://github.com/BAWES-Universe/studenthub-finance) | **Angular** | Finance team | ✅ Production reference |
| [studenthub-team](https://github.com/BAWES-Universe/studenthub-team) | **Angular** | Team reporting | ✅ Production reference |

### 🟡 Newer / Experimental Frontend Repos

These are newer implementations — some experimental, some in active development. They are **not yet in production** as the primary user-facing apps.

| Repo | Stack | Purpose | Status |
|---|---|---|---|
| [studenthub-candidate-react](https://github.com/BAWES-Universe/studenthub-candidate-react) | **Ionic + React** | Candidate app rebuild | 🧪 Experimental |
| [studenthub-candidate-next](https://github.com/BAWES-Universe/studenthub-candidate-next) | **Next.js** | Candidate app (web-first) | 🚧 In development |

### 🟣 Supporting Services

| Repo | Stack | Purpose |
|---|---|---|
| [studenthub-microservices](https://github.com/BAWES-Universe/studenthub-microservices) | Node.js | Microservices layer |
| [studenthub-pbx](https://github.com/BAWES-Universe/studenthub-pbx) | Go | Call center / PBX integration |
| [studenthub-personas](https://github.com/BAWES-Universe/studenthub-personas) | JavaScript | Landing pages for different user personas |

---

## Frontend Tech Stack

### The Angular Apps (Production — Use These as Reference)

The core frontend applications (`candidate`, `company`, `staff`, `inspector`, `admin`, `manager`, `finance`, `team`) are all built with:

- **Framework:** Angular
- **Mobile wrapper:** Ionic (for the candidate/company/staff/inspector apps)
- **Language:** TypeScript
- **API communication:** HTTP client talking to this Yii2 backend
- **Auth:** Bearer token via the `candidate/`, `company/`, `staff/` API apps respectively

**If you are implementing a feature that has a UI component**, always check the matching Angular repo first. The Angular apps contain the established screen layouts, form validations, API call patterns, and error handling that are already in production. Do not reinvent these patterns.

### The Newer Frontends (Development)

Two newer candidate app approaches are in progress:

- **`studenthub-candidate-react`** — Built with Ionic + React. An alternative mobile-first implementation exploring the React ecosystem.
- **`studenthub-candidate-next`** — Built with Next.js. A web-first rebuild targeting modern SSR/SSG patterns.

Neither has replaced the Angular app in production. If you are contributing to this backend and need to understand how the candidate-facing API is consumed, **start with [studenthub-candidate](https://github.com/BAWES-Universe/studenthub-candidate) (Angular)** as it is the most complete and production-proven reference.

---

## Backend Apps In This Repo

This is a **Yii2 Advanced Application** monorepo. Each subdirectory is a separate Yii2 application with its own controllers, models, web entry point, and config.

```
studenth ub/
├── admin/          # Admin panel backend API
├── candidate/      # Candidate (trainee) REST API
├── company/        # Company (employer) REST API
├── inspector/      # Inspector REST API
├── staff/          # Internal staff REST API
├── manager/        # Manager REST API
├── verification/   # Document verification API
├── status/         # System status
├── common/         # Shared models, components, fixtures
├── console/        # CLI commands (migrations, seeds)
├── cron/           # Scheduled jobs
├── docs/           # Full documentation
├── environments/   # Environment-specific configs (dev/prod)
└── nginx/          # Nginx config files
```

### Shared Code

All apps share the `common/` directory which contains:
- **Models** — ActiveRecord models for every database table
- **Components** — Reusable components (mail, auth, payments)
- **Fixtures** — Database seed data for development
- **Config** — Shared configuration (database, params)

If you add a new model or modify database structure, do it in `common/` — not inside individual apps.

---

## Getting Started

### Prerequisites

- Docker + Docker Compose
- Git
- PHP 8.2+ (for running composer outside Docker)

### 1. Clone & Configure

```bash
git clone https://github.com/BAWES-Universe/studenthub.git
cd studenthub
```

Copy environment config:
```bash
cp environments/dev/common/config/main-local.php.example environments/dev/common/config/main-local.php
cp environments/dev/common/config/params-local.php.example environments/dev/common/config/params-local.php
```

### 2. Start with Docker (Local)

```bash
# Start all services (PHP + Nginx + MySQL)
docker-compose -f docker-compose-local.yml -p studenthub-local-server up -d

# Watch logs
docker-compose -f docker-compose-local.yml logs -f
```

### 3. Initialize the Application

```bash
# Enter the backend container
docker exec -it studenthub-backend-dev /bin/bash

# Run Yii2 init (choose Development environment)
./init

# Install PHP dependencies
composer install

# Run database migrations
./yii migrate
```

### 4. Access the APIs

| App | URL |
|---|---|
| Candidate API | http://localhost:22080 |
| Company API | http://localhost:23080 |
| Staff API | http://localhost:25080 |
| Admin API | http://localhost:21080 |
| Inspector API | http://localhost:24080 |
| Verification API | http://localhost:26080 |
| Gii (Code Generator) | http://localhost:21080/gii |

### 5. Flush Cache After Schema Changes

If you apply a migration but get "column not found" errors:

```bash
docker exec -it studenthub-backend-dev /bin/bash
./yii cache/flush-schema db
./yii cache/flush cache
```

Or manually clear runtime caches:
```bash
rm -rf /var/www/html/admin/runtime/cache
rm -rf /var/www/html/candidate/runtime/cache
rm -rf /var/www/html/company/runtime/cache
rm -rf /var/www/html/staff/runtime/cache
rm -rf /var/www/html/common/runtime/cache
# (repeat for inspector, manager, status, verification, console)
```

---

## Documentation

Full docs are in the `docs/` directory:

| Doc | Description |
|---|---|
| [Setup Guide](docs/setup.md) | Detailed installation and configuration |
| [User Roles](docs/user-roles.md) | All user types and their permissions |
| [API Endpoints](docs/api-endpoints.md) | Available REST endpoints per app |
| [Database](docs/database/README.md) | Schema diagrams and table documentation |
| [Cron Jobs](docs/cron-jobs.md) | Scheduled tasks and their schedules |
| [Analytics](docs/analytics.md) | Event tracking setup |

---

## Running Tests

```bash
./run-tests.sh
```

---

## Docker Reference

### Local Development
```bash
docker-compose -f docker-compose-local.yml -p studenthub-local-server up -d
docker-compose -f docker-compose-local.yml -p studenthub-local-server down
```

### Dev Server
```bash
docker-compose -f docker-compose-dev.yml -p studenthub-dev-server up -d
docker-compose -f docker-compose-dev.yml -p studenthub-dev-server down
```

### Production
```bash
docker-compose -f docker-compose-prod.yml -p studenthub-prod-server up -d
```

### Rebuild Images
```bash
docker-compose -f docker-compose-dev.yml -p studenthub-dev-server build
# Cross-platform (Apple Silicon → Linux)
docker buildx build --platform linux/amd64 -t studenthub/backend-dev -f Dockerfile-nginx-dev .
docker buildx build --platform linux/amd64 -t studenthub/backend-prod -f Dockerfile-nginx-prod .
```

### ECR Push (AWS)
```bash
# Login
aws ecr get-login-password --region eu-west-2 | docker login --username AWS --password-stdin 438663597141.dkr.ecr.eu-west-2.amazonaws.com

# Tag & Push (dev)
docker tag studenthub/backend-dev:latest 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-dev:latest
docker push 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-dev:latest

# Tag & Push (prod)
docker tag studenthub/backend-prod:latest 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest
docker push 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest
```

---

## DNS / Local Hosts Setup

To use local domain names instead of `localhost:PORT`, add to `/etc/hosts`:

```
127.0.0.1 student.api.studenthub.co
```

Then flush DNS cache:
```bash
sudo killall -HUP mDNSResponder
```

> ⚠️ Use `http://` (not `https://`) in local development.

---

## Contributing via Bounty

This project uses [Algora](https://algora.io) for bounties across all StudentHub repos. When claiming a bounty:

- [ ] Read the **Ecosystem Map** above — confirm which API app and frontend the issue touches
- [ ] If the issue has a UI component, read the matching Angular frontend repo before coding
- [ ] New database columns → add to `common/` models, then run `./yii migrate`
- [ ] New API endpoints → add controller in the correct app directory (`candidate/`, `company/`, etc.)
- [ ] Flush schema cache after any migration
- [ ] Run `./run-tests.sh` before submitting your PR
- [ ] Reference the issue number in your PR title

---

## License

See [LICENSE.md](LICENSE.md)
