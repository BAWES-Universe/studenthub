# Dockerfiles Explained

## Active Dockerfiles (4)

### Local Development
- **`Dockerfile-nginx-dev`**
  - **Used by:** `docker-compose.yaml` (default local dev)
  - **Purpose:** Local development with nginx
  - **Config:** Uses `nginx/development.conf`
  - **Environment:** Development
  - **Status:** ✅ Active

### Self-Hosted Production
- **`Dockerfile-nginx-prod`**
  - **Used by:** `docker-compose-prod.yaml`
  - **Purpose:** Production deployment on self-hosted servers (AWS EC2, VPS, etc.)
  - **Config:** Uses `nginx/production.conf`
  - **Environment:** Production
  - **Status:** ✅ Active

### Railway Development
- **`Dockerfile-nginx-dev-railway`**
  - **Used by:** Railway dev deployments
  - **Purpose:** Railway development environment
  - **Config:** Uses `nginx/railway-dev.conf`
  - **Environment:** Railway Dev
  - **Status:** ✅ Active
  - **Built by:** GitHub Actions on `develop` and `develop-cleanup` branches

### Railway Production
- **`Dockerfile-nginx-railway`**
  - **Used by:** Railway production deployments
  - **Purpose:** Railway production environment
  - **Config:** Uses `nginx/railway-prod.conf`
  - **Environment:** Railway Prod
  - **Status:** ✅ Active
  - **Built by:** GitHub Actions on `master` and releases

## Unused Dockerfiles (2)

### Legacy/Unused
- **`Dockerfile-nginx-local`**
  - **Status:** ❌ Unused
  - **Reason:** Redundant with `Dockerfile-nginx-dev`
  - **Action:** Can be removed

- **`Dockerfile-apache`**
  - **Status:** ❌ Unused
  - **Reason:** Legacy Apache setup, not used
  - **Action:** Can be removed

## Subdirectory Dockerfiles

There are also Dockerfiles in subdirectories (`admin/Dockerfile`, `candidate/Dockerfile`, etc.) from the old multi-container architecture. These are **not used** in the current setup.

## GitHub Actions Build Matrix

### For `develop-cleanup` branch:
- ✅ `Dockerfile-nginx-prod` → `backend-prod:dev-cleanup`
- ✅ `Dockerfile-nginx-dev-railway` → `backend-railway-dev:dev-cleanup`
- ❌ `Dockerfile-nginx-railway` → Skipped (only builds on master/release)

**Total: 2 builds** (not 3)

### For `develop` branch:
- ✅ `Dockerfile-nginx-prod` → `backend-prod:dev`
- ✅ `Dockerfile-nginx-dev-railway` → `backend-railway-dev:dev`
- ❌ `Dockerfile-nginx-railway` → Skipped

**Total: 2 builds**

### For `master` branch:
- ✅ `Dockerfile-nginx-prod` → `backend-prod:latest`
- ✅ `Dockerfile-nginx-railway` → `backend-railway-prod:latest`
- ❌ `Dockerfile-nginx-dev-railway` → Skipped (only builds on develop branches)

**Total: 2 builds**

### For releases:
- ✅ `Dockerfile-nginx-prod` → `backend-prod:{release-tag}`
- ✅ `Dockerfile-nginx-railway` → `backend-railway-prod:{release-tag}`
- ❌ `Dockerfile-nginx-dev-railway` → Skipped

**Total: 2 builds**

## Why Different Dockerfiles?

1. **Local Dev vs Production:** Different nginx configs, debug tools
2. **Railway vs Self-Hosted:** Railway has specific requirements (domain routing, no port-based)
3. **Dev vs Prod:** Different environment variables, optimizations

## Recommendations

1. **Remove unused Dockerfiles:**
   - `Dockerfile-nginx-local`
   - `Dockerfile-apache`
   - Subdirectory Dockerfiles (if confirmed unused)

2. **Keep active Dockerfiles:**
   - All 4 active Dockerfiles serve distinct purposes
   - Each targets a specific deployment scenario

3. **Documentation:**
   - Keep this document updated
   - Add comments in Dockerfiles explaining their purpose

