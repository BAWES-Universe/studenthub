# Architecture Overview

## Current Architecture

### Local Development (Docker Compose)

```
┌─────────────────────────────────────────────────────────┐
│                    Traefik (Port 80)                    │
│              http://localhost:8080 (dashboard)          │
└──────────────────────┬──────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
   ┌────▼────┐    ┌────▼────┐   ┌────▼────┐
   │  Admin │    │Candidate│   │ Company │
   │ :21080 │    │ :22080  │   │ :23080  │
   └────────┘    └─────────┘   └─────────┘
        │              │              │
        └──────────────┼──────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
   ┌────▼────┐    ┌────▼────┐   ┌────▼────┐
   │Inspector│    │  Staff  │   │Verification│
   │ :24080  │    │ :25080  │   │  :26080   │
   └─────────┘    └─────────┘   └──────────┘
        │              │              │
        └──────────────┼──────────────┘
                       │
              ┌────────▼────────┐
              │  App Container  │
              │  (Nginx + PHP)  │
              └────────┬────────┘
                       │
        ┌───────────────┼───────────────┐
        │               │               │
   ┌────▼────┐    ┌────▼────┐    ┌────▼────┐
   │  MySQL  │    │  Redis  │    │  Volumes│
   │ :3307   │    │ :6379   │    │  (data) │
   └─────────┘    └─────────┘    └─────────┘
```

**Access URLs:**
- `admin.studenthub.local` → Admin app
- `candidate.studenthub.local` → Candidate app
- `company.studenthub.local` → Company app
- `inspector.studenthub.local` → Inspector app
- `staff.studenthub.local` → Staff app
- `verification.studenthub.local` → Verification app

### Production (Railway)

```
┌─────────────────────────────────────────────┐
│         Railway Router (HTTPS)              │
│    (Automatic SSL, Domain Management)       │
└──────────────────────┬──────────────────────┘
                       │
              ┌────────▼────────┐
              │  App Container  │
              │  (Nginx + PHP)  │
              │                 │
              │  Domain Routing:│
              │  - admin.api.*   │
              │  - student.api.* │
              │  - employer.api.*│
              │  - etc.          │
              └────────┬─────────┘
                       │
        ┌───────────────┼───────────────┐
        │               │               │
   ┌────▼────┐    ┌────▼────┐    ┌────▼────┐
   │  MySQL  │    │  Redis  │    │  Volumes│
   │(Railway)│    │(Railway)│    │  (data) │
   └─────────┘    └─────────┘    └─────────┘
```

**Access URLs:**
- `admin.api.dev.studenthub.co` → Admin app
- `student.api.dev.studenthub.co` → Candidate app
- `employer.api.dev.studenthub.co` → Company app
- `inspector.api.dev.studenthub.co` → Inspector app
- `staff.api.dev.studenthub.co` → Staff app
- `verification.dev.studenthub.co` → Verification app

## Component Details

### Traefik (Local Dev Only)

- **Purpose:** Reverse proxy for local development
- **Port:** 80 (HTTP), 8080 (Dashboard)
- **Configuration:** `traefik/traefik.yml`
- **Routing:** Host-based (domain names)
- **Benefits:** No port memorization, clean URLs

### Nginx (Production)

- **Purpose:** Web server and reverse proxy
- **Configuration:** `nginx/railway-*.conf`
- **Routing:** Server name-based (domain names)
- **Benefits:** Production-ready, Railway-compatible

### PHP-FPM

- **Version:** 8.2
- **Extensions:** gd, pdo_mysql, zip, opcache, exif
- **Configuration:** `nginx/php.conf`

### MySQL

- **Version:** 9
- **Local Port:** 3307 (mapped from 3306)
- **Production:** Railway managed service

### Redis

- **Version:** 6.2.3
- **Port:** 6379
- **Purpose:** Caching, sessions

## Docker Compose Files

### `docker-compose.yaml` (Default)
- **Purpose:** Local development
- **Features:** Traefik, MySQL, Redis, volume mounts
- **Use:** `docker-compose up` (no -f flag needed)

### `docker-compose-prod.yaml`
- **Purpose:** Production (self-hosted)
- **Features:** App, Redis (no MySQL - external)
- **Use:** `docker-compose -f docker-compose-prod.yaml up`

### `docker-compose.phpmyadmin.yaml`
- **Purpose:** Optional phpMyAdmin
- **Use:** `docker-compose -f docker-compose.phpmyadmin.yaml up`

## Dockerfiles

### Development
- `Dockerfile-nginx-dev` - Local dev with nginx
- `Dockerfile-nginx-dev-railway` - Railway dev deployment

### Production
- `Dockerfile-nginx-prod` - Self-hosted production
- `Dockerfile-nginx-railway` - Railway production deployment

## CI/CD Pipeline

### GitHub Actions

```
Push/PR/Release
      │
      ▼
┌─────────────┐
│  Checkout   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Build Image │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Push to GHCR│
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Railway    │
│  (Deploy)   │
└─────────────┘
```

## Network Architecture

### Local Development
- **Network:** `studenthub-network` (bridge)
- **Services:** All containers on same network
- **Communication:** Service names (e.g., `mysql`, `redis`)

### Production (Railway)
- **Network:** Railway managed
- **Services:** Railway services communicate via internal network
- **External:** Railway handles routing and SSL

## Data Persistence

### Local Development
- **MySQL:** `mysql-data` volume
- **Redis:** `redis-data` volume
- **App Code:** Volume mount (live code changes)

### Production (Railway)
- **MySQL:** Railway managed database
- **Redis:** Railway managed service
- **App Code:** Built into image

## Security Considerations

### Local Development
- Traefik dashboard: HTTP only (local)
- MySQL: Exposed on port 3307
- Redis: Exposed on port 6379

### Production
- HTTPS: Automatic via Railway
- Database: Railway managed (not exposed)
- Redis: Railway managed (not exposed)
- Secrets: Environment variables

## Migration Path

### From Port-Based to Traefik
1. ✅ Traefik configuration created
2. ✅ Docker Compose updated
3. ⏳ Update documentation
4. ⏳ Team training

### From Manual Builds to GitHub Actions
1. ✅ GitHub Actions workflow created
2. ⏳ Configure Railway to use GHCR images
3. ⏳ Update deployment documentation
4. ⏳ Monitor first few builds

## Next Steps

- [ ] Add health checks to services
- [ ] Implement service monitoring
- [ ] Add HTTPS to local dev (optional)
- [ ] Set up automated testing in CI/CD
- [ ] Configure image scanning

