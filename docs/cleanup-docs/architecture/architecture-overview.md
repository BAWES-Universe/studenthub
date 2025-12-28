# Architecture Overview

## Current Architecture

### Local Development (Docker Compose)

```
┌─────────────────────────────────────────────────────────┐
│                    Traefik Service                      │
│         http://traefik.studenthub.local (dashboard)     │
│                    Port 80 (HTTP)                       │
└──────────────────────┬──────────────────────────────────┘
                       │
              ┌────────▼────────┐
              │  App Container  │
              │  (Nginx + PHP)  │
              │                 │
              │  Internal Ports: │
              │  - :21080 (Admin)│
              │  - :22080 (Candidate)│
              │  - :23080 (Company)│
              │  - :24080 (Inspector)│
              │  - :25080 (Staff)│
              │  - :26080 (Verification)│
              └────────┬─────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
   ┌────▼────┐    ┌────▼────┐   ┌────▼────┐
   │  MySQL  │    │  Redis  │   │ Volumes │
   │ Service │    │ Service │   │ (data)  │
   │ :3307   │    │ :6379   │   │         │
   └─────────┘    └─────────┘   └─────────┘
        │              │              │
        └──────────────┼──────────────┘
                       │
              ┌────────▼────────┐
              │ studenthub-network │
              │    (bridge)        │
              └────────────────────┘
```

**Service Architecture:**
- **Traefik Service** - Reverse proxy (separate container)
  - Routes domain names to app container ports
  - Dashboard accessible via domain name
  
- **App Container** - Nginx + PHP-FPM (separate container)
  - Serves all 6 applications on different internal ports
  - Connects to MySQL and Redis services
  
- **MySQL Service** - Database (separate container)
  - Exposed on port 3307 (mapped from 3306)
  - Connected via Docker network
  
- **Redis Service** - Cache/Sessions (separate container)
  - Exposed on port 6379
  - Connected via Docker network

**Access URLs:**
- `traefik.studenthub.local` → Traefik dashboard
- `admin.studenthub.local` → Admin app (via Traefik → App :21080)
- `candidate.studenthub.local` → Candidate app (via Traefik → App :22080)
- `company.studenthub.local` → Company app (via Traefik → App :23080)
- `inspector.studenthub.local` → Inspector app (via Traefik → App :24080)
- `staff.studenthub.local` → Staff app (via Traefik → App :25080)
- `verification.studenthub.local` → Verification app (via Traefik → App :26080)

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

