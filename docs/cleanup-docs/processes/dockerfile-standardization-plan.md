# Dockerfile Standardization Plan

## Overview

This document outlines the standardization of all Dockerfiles to follow best practices, improve consistency, and ensure maintainability.

## Changes Implemented

### ✅ Standardization Complete

All 4 Dockerfiles have been standardized with the following improvements:

1. **Composer Install in Build Layer**
   - Moved from CMD to RUN (build layer)
   - Better Docker layer caching
   - Faster rebuilds
   - Production uses `--no-dev` flag

2. **Environment Names Fixed**
   - Changed from `Dev-Server-Nginx` → `Development`
   - Changed from `Production-Nginx` → `Production`
   - Changed from `Dev-Server-Railway` → `Development`
   - Changed from `Production-Railway` → `Production`
   - All now use correct names from `environments/index.php`

3. **Dependencies Standardized**
   - Added `chromium` to all Dockerfiles (required for ID card screenshots)
   - Added `default-mysql-client` to all Dockerfiles (useful for migrations, debugging)
   - Consistent dependency list across all files

4. **Migrations Removed from CMD**
   - Migrations no longer run in Dockerfile CMD
   - Prevents accidental re-runs on container restart
   - Allows separate migration control
   - See [Migration Strategy](#migration-strategy) below

5. **Code Cleanup**
   - Removed all commented code
   - Standardized structure
   - Clear, concise comments
   - Consistent formatting

6. **Removed Missing References**
   - Removed `./deployment.sh` reference (file doesn't exist)
   - Cleaned up unused commented commands

## Standardized Dockerfile Structure

All Dockerfiles now follow this consistent structure:

```dockerfile
FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    [dependencies] \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip opcache exif \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install PHP dependencies (in build layer for caching)
RUN composer install [--no-dev] --optimize-autoloader --no-interaction

# Set permissions
RUN chmod -R 775 /var/www/html

# Setup cron
COPY ./cron/cronlist /etc/cron.d/cronlist
RUN chmod 0644 /etc/cron.d/cronlist && crontab /etc/cron.d/cronlist

# Copy nginx configuration
COPY ./nginx/php.conf /etc/nginx/php.conf
COPY ./nginx/{config}.conf /etc/nginx/sites-available/app.conf
RUN ln -s /etc/nginx/sites-available/app.conf /etc/nginx/sites-enabled/ && \
    rm -f /etc/nginx/sites-enabled/default

# Create symlink for convenience
RUN ln -s /var/www/html ~/www

# Expose port
EXPOSE 80

# Start services (migrations run separately)
CMD ["sh", "-c", \
     "./init --env={ENV_NAME} --overwrite=All && \
      service cron start && \
      nginx -t && service nginx start && \
      php-fpm && tail -f /dev/null"]
```

## Dockerfile Comparison

| Feature | Dev | Prod | Railway Prod | Railway Dev |
|---------|-----|------|--------------|-------------|
| **Composer in Build** | ✅ | ✅ | ✅ | ✅ |
| **Environment Name** | `Development` | `Production` | `Production` | `Development` |
| **Composer Flags** | `--optimize-autoloader` | `--no-dev --optimize-autoloader` | `--no-dev --optimize-autoloader` | `--optimize-autoloader` |
| **Chromium** | ✅ | ✅ | ✅ | ✅ |
| **MySQL Client** | ✅ | ✅ | ✅ | ✅ |
| **Nginx Config** | `development.conf` | `production.conf` | `railway-prod.conf` | `railway-dev.conf` |
| **Migrations in CMD** | ❌ | ❌ | ❌ | ❌ |

## Migration Strategy

### Why Migrations Were Removed from Dockerfile CMD

**Problems with running migrations in CMD:**
- Migrations run on every container start/restart
- No way to run migrations separately
- Can't rollback easily
- Blocks container startup if migration fails
- No visibility into migration status

**Best Practice:** Migrations should run once per deployment, not per container start.

### Implementation by Platform

#### Railway (Production & Development)

**Method:** Post-Deploy Hook

1. **Configure in Railway Service Settings:**
   - Go to your Railway service
   - Settings → Deploy
   - **Post Deploy Command:** `./yii migrate --interactive=0`

2. **Alternative: Create Script**
   Create `scripts/railway-post-deploy.sh`:
   ```bash
   #!/bin/bash
   set -e
   echo "Running database migrations..."
   ./yii migrate --interactive=0
   echo "Migrations completed successfully"
   ```
   Then set Post Deploy Command to: `chmod +x scripts/railway-post-deploy.sh && ./scripts/railway-post-deploy.sh`

**Benefits:**
- Runs automatically after each deployment
- Only runs once per deployment
- Can see migration output in Railway logs
- Doesn't block container startup

#### Self-Hosted Production

**Option 1: Init Container (Recommended)**

Add to `docker-compose-prod.yaml`:
```yaml
services:
  migrate:
    image: ghcr.io/your-org/studenthub/backend-prod:latest
    command: ./yii migrate --interactive=0
    depends_on:
      mysql:
        condition: service_healthy
    restart: "no"
    networks:
      - studenthub-network
    environment:
      - DB_HOST=${DB_HOST}
      - DB_NAME=${DB_NAME}
      - DB_USER=${DB_USER}
      - DB_PASSWORD=${DB_PASSWORD}
      # ... other env vars
```

**Usage:**
```bash
docker-compose -f docker-compose-prod.yaml up migrate
docker-compose -f docker-compose-prod.yaml up -d app redis
```

**Option 2: Manual Migration**

```bash
# After pulling new image
docker exec -it studenthub-backend-prod ./yii migrate --interactive=0
```

**Option 3: Deployment Script**

Create `scripts/deploy-prod.sh`:
```bash
#!/bin/bash
set -e

echo "Pulling latest image..."
docker pull ghcr.io/your-org/studenthub/backend-prod:latest

echo "Running migrations..."
docker run --rm \
  --network studenthub-network \
  -e DB_HOST=mysql \
  -e DB_NAME=${DB_NAME} \
  -e DB_USER=${DB_USER} \
  -e DB_PASSWORD=${DB_PASSWORD} \
  ghcr.io/your-org/studenthub/backend-prod:latest \
  ./yii migrate --interactive=0

echo "Restarting application..."
docker-compose -f docker-compose-prod.yaml up -d --no-deps app
```

#### Local Development

**Option 1: Init Container (Recommended)**

Add to `docker-compose.yaml`:
```yaml
services:
  migrate:
    build:
      context: .
      dockerfile: ./Dockerfile-nginx-dev
    command: ./yii migrate --interactive=0
    depends_on:
      - mysql
    restart: "no"
    networks:
      - studenthub-network
```

**Usage:**
```bash
docker-compose up migrate
docker-compose up -d
```

**Option 2: Keep in CMD (Convenient for Dev)**

For local development only, you can temporarily add migrations back to CMD:
```dockerfile
CMD ["sh", "-c", \
     "./init --env=Development --overwrite=All && \
      ./yii migrate --interactive=0 && \
      service cron start && \
      nginx -t && service nginx start && \
      php-fpm && tail -f /dev/null"]
```

**Note:** This is acceptable for local dev but NOT for production.

## Chromium Requirement

**Confirmed:** Chromium is required for ID card screenshot generation.

**Usage:** `staff/models/CandidateIdCard.php` uses `/usr/bin/chromium` to take screenshots of candidate ID cards.

**All Dockerfiles now include:** `chromium` in the dependency list.

## MySQL Client Requirement

**Included in all Dockerfiles:** `default-mysql-client`

**Reasons:**
- Useful for running migrations
- Helpful for debugging database issues
- Can be used for backups
- Small overhead

## Environment Variables

See `.env.template` for all environment variables that should be configured.

**Key Points:**
- Copy `.env.template` to `.env` and fill in values
- For environment-specific configs, use `environments/{dev|prod}/.env`
- Never commit `.env` files (already in `.gitignore`)

## Benefits of Standardization

1. **Consistency:** All Dockerfiles follow same pattern
2. **Caching:** Composer in build layer = faster rebuilds
3. **Reliability:** Migrations separate = no accidental re-runs
4. **Maintainability:** Less code, clearer purpose
5. **Best Practices:** Follows Docker best practices
6. **Flexibility:** Can run migrations separately, rollback easily

## Verification Checklist

After standardization, verify:

- [x] All Dockerfiles use correct environment names
- [x] Composer install is in build layer
- [x] Migrations removed from CMD
- [x] Chromium included in all Dockerfiles
- [x] MySQL client included in all Dockerfiles
- [x] All commented code removed
- [x] Consistent structure across all files
- [x] Production uses `--no-dev` flag
- [x] Railway Dockerfiles use correct environment names

## Next Steps

1. ✅ Standardize Dockerfiles
2. ✅ Create `.env.template`
3. ⏳ Configure Railway post-deploy hooks
4. ⏳ Update docker-compose files with init containers (optional)
5. ⏳ Test builds and deployments
6. ⏳ Update deployment documentation

## Migration Guide

### For Existing Deployments

1. **Railway:**
   - Update Dockerfile (already done)
   - Configure post-deploy hook in Railway settings
   - Deploy and verify migrations run

2. **Self-Hosted:**
   - Update Dockerfile (already done)
   - Choose migration strategy (init container or manual)
   - Update deployment process
   - Test on staging first

3. **Local Dev:**
   - Update Dockerfile (already done)
   - Rebuild: `docker-compose build`
   - Test: `docker-compose up`

## References

- [Dockerfile Best Practices](https://docs.docker.com/develop/develop-images/dockerfile_best-practices/)
- [Yii2 Migrations](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations)
- [Railway Post-Deploy Hooks](https://docs.railway.app/deploy/builds#post-deploy-command)

