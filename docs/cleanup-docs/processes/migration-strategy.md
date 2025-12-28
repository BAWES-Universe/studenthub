# Database Migration Strategy

## Overview

Database migrations are now handled separately from container startup to follow best practices and improve reliability.

## Why Separate Migrations?

### Problems with Running Migrations in Dockerfile CMD

1. **Runs on Every Container Start**
   - Migrations execute every time container restarts
   - Can cause issues if migrations are not idempotent
   - Wastes time on unnecessary operations

2. **No Control**
   - Can't run migrations separately
   - Can't skip migrations if needed
   - Can't rollback easily

3. **Blocks Startup**
   - If migration fails, container won't start
   - Hard to debug migration issues
   - No way to start app without migrations

4. **No Visibility**
   - Migration output mixed with app logs
   - Hard to see migration status
   - Difficult to troubleshoot

### Benefits of Separate Migrations

1. **Run Once Per Deployment**
   - Migrations execute only when needed
   - Clear separation of concerns
   - Better control over deployment process

2. **Better Error Handling**
   - Can see migration output clearly
   - Can retry migrations if needed
   - App can start even if migrations fail (with proper handling)

3. **Flexibility**
   - Can run migrations manually
   - Can skip migrations if needed
   - Can rollback easily

## Implementation by Platform

### Railway (Production & Development)

**Method:** Post-Deploy Hook

Railway supports post-deploy commands that run after the container starts.

#### Setup

1. **Go to Railway Service Settings:**
   - Navigate to your service
   - Click "Settings"
   - Go to "Deploy" section

2. **Configure Post Deploy Command:**
   - **Post Deploy Command:** `./yii migrate --interactive=0`
   - Save settings

3. **Deploy:**
   - Railway will:
     1. Build and deploy the container
     2. Start the container
     3. Run the post-deploy command (migrations)
     4. Container continues running

#### Alternative: Using a Script

Create `scripts/railway-post-deploy.sh`:
```bash
#!/bin/bash
set -e

echo "========================================="
echo "Running database migrations..."
echo "========================================="

./yii migrate --interactive=0

echo "========================================="
echo "Migrations completed successfully"
echo "========================================="
```

Then set Post Deploy Command to:
```bash
chmod +x scripts/railway-post-deploy.sh && ./scripts/railway-post-deploy.sh
```

#### Verification

After deployment, check Railway logs:
- Look for migration output
- Verify migrations ran successfully
- Check for any errors

### Self-Hosted Production

**Recommended:** Init Container Pattern

#### Option 1: Init Container (Docker Compose)

Add migration service to `docker-compose-prod.yaml`:

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
      - DB_HOST=${DB_HOST:-mysql}
      - DB_NAME=${DB_NAME:-studenthub}
      - DB_USER=${DB_USER:-studenthubuser}
      - DB_PASSWORD=${DB_PASSWORD}
      - WALLET_DB_HOST=${WALLET_DB_HOST:-mysql}
      - WALLET_DB_NAME=${WALLET_DB_NAME:-wallet}
      - WALLET_DB_USER=${WALLET_DB_USER:-studenthubuser}
      - WALLET_DB_PASSWORD=${WALLET_DB_PASSWORD}
    env_file:
      - .env
```

**Usage:**
```bash
# Run migrations first
docker-compose -f docker-compose-prod.yaml up migrate

# Then start the application
docker-compose -f docker-compose-prod.yaml up -d app redis
```

**Benefits:**
- Migrations run before app starts
- Can see migration output clearly
- Easy to retry if needed
- App only starts after migrations succeed

#### Option 2: Manual Migration

```bash
# After pulling new image
docker exec -it studenthub-backend-prod ./yii migrate --interactive=0
```

**When to use:**
- Quick deployments
- Testing migrations before full deployment
- Troubleshooting migration issues

#### Option 3: Deployment Script

Create `scripts/deploy-prod.sh`:

```bash
#!/bin/bash
set -e

IMAGE="ghcr.io/your-org/studenthub/backend-prod:latest"
NETWORK="studenthub-network"

echo "========================================="
echo "Pulling latest image..."
echo "========================================="
docker pull $IMAGE

echo "========================================="
echo "Running migrations..."
echo "========================================="
docker run --rm \
  --network $NETWORK \
  --env-file .env \
  $IMAGE \
  ./yii migrate --interactive=0

echo "========================================="
echo "Restarting application..."
echo "========================================="
docker-compose -f docker-compose-prod.yaml up -d --no-deps app

echo "========================================="
echo "Deployment complete!"
echo "========================================="
```

**Usage:**
```bash
chmod +x scripts/deploy-prod.sh
./scripts/deploy-prod.sh
```

### Local Development

**Recommended:** Init Container Pattern

#### Option 1: Init Container (Docker Compose)

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
    volumes:
      - .:/var/www/html
```

**Usage:**
```bash
# Run migrations first
docker-compose up migrate

# Then start the application
docker-compose up -d
```

#### Option 2: Keep in CMD (Convenient for Dev)

For local development only, you can temporarily add migrations back to CMD in `Dockerfile-nginx-dev`:

```dockerfile
CMD ["sh", "-c", \
     "./init --env=Development --overwrite=All && \
      ./yii migrate --interactive=0 && \
      service cron start && \
      nginx -t && service nginx start && \
      php-fpm && tail -f /dev/null"]
```

**Note:** This is acceptable for local dev but NOT for production.

#### Option 3: Manual Migration

```bash
docker-compose exec app ./yii migrate --interactive=0
```

## Migration Best Practices

### 1. Always Test Migrations First

```bash
# Test on local/staging first
docker-compose up migrate

# Check for errors
docker-compose logs migrate
```

### 2. Backup Before Migrations

```bash
# Backup database before running migrations
docker exec mysql mysqldump -u root -p studenthub > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 3. Monitor Migration Output

Always check migration logs:
- Railway: Check deployment logs
- Self-hosted: Check init container logs
- Local: Check docker-compose logs

### 4. Handle Migration Failures

If migrations fail:
1. **Don't panic** - app can still run (if migrations are backward compatible)
2. **Check logs** - see what went wrong
3. **Fix the issue** - update migration or database
4. **Retry** - run migrations again

### 5. Idempotent Migrations

Ensure migrations are idempotent (can run multiple times safely):
- Use `IF NOT EXISTS` for tables/columns
- Check before creating indexes
- Use transactions where possible

## Troubleshooting

### Migrations Not Running

**Railway:**
- Check Post Deploy Command is set correctly
- Check Railway logs for errors
- Verify `./yii` is executable

**Self-Hosted:**
- Check init container logs: `docker-compose logs migrate`
- Verify database connection
- Check environment variables

**Local:**
- Check docker-compose logs: `docker-compose logs migrate`
- Verify MySQL is running
- Check database credentials

### Migration Errors

**Common Issues:**

1. **Database Connection Failed**
   - Check DB_HOST, DB_NAME, DB_USER, DB_PASSWORD
   - Verify database is accessible
   - Check network connectivity

2. **Migration Already Applied**
   - Check migration history table
   - Use `./yii migrate/history` to see applied migrations
   - Use `./yii migrate/new` to see pending migrations

3. **Permission Errors**
   - Check file permissions
   - Verify database user has correct permissions

## Migration Commands Reference

```bash
# Run all pending migrations
./yii migrate --interactive=0

# See migration history
./yii migrate/history

# See pending migrations
./yii migrate/new

# Rollback last migration
./yii migrate/down 1

# Mark migration as applied without running
./yii migrate/mark migration_name

# Create new migration
./yii migrate/create migration_name
```

## Summary

| Platform | Method | Command |
|----------|--------|---------|
| **Railway** | Post-Deploy Hook | `./yii migrate --interactive=0` |
| **Self-Hosted** | Init Container | `docker-compose up migrate` |
| **Self-Hosted** | Manual | `docker exec -it app ./yii migrate --interactive=0` |
| **Local Dev** | Init Container | `docker-compose up migrate` |
| **Local Dev** | Manual | `docker-compose exec app ./yii migrate --interactive=0` |

## References

- [Yii2 Migrations Guide](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations)
- [Railway Post-Deploy Hooks](https://docs.railway.app/deploy/builds#post-deploy-command)
- [Docker Init Containers](https://docs.docker.com/compose/startup-order/)

