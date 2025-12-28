# Quick Start Guide

## Local Development Setup

### Prerequisites

- Docker and Docker Compose installed
- Git
- Text editor

### Step 1: Clone Repository

```bash
git clone <repository-url>
cd studenthub
```

### Step 2: Setup Environment Variables

Copy the environment template to create your `.env` file:

```bash
cp .env.template .env
```

The `.env.template` file contains default values that work with the Docker Compose development setup. You can edit `.env` later if you need to customize any values.

### Step 3: Configure Hosts File

**Linux/macOS:**
```bash
sudo nano /etc/hosts
```

**Windows:**
Edit `C:\Windows\System32\drivers\etc\hosts`

Add:
```
127.0.0.1 traefik.studenthub.local
127.0.0.1 admin.studenthub.local
127.0.0.1 candidate.studenthub.local
127.0.0.1 company.studenthub.local
127.0.0.1 inspector.studenthub.local
127.0.0.1 staff.studenthub.local
127.0.0.1 verification.studenthub.local
127.0.0.1 phpmyadmin.studenthub.local
```

### Step 4: Start Services

```bash
docker-compose up -d
```

### Step 5: Access Applications

- **Traefik Dashboard:** http://traefik.studenthub.local
- **Admin:** http://admin.studenthub.local
- **Candidate:** http://candidate.studenthub.local
- **Company:** http://company.studenthub.local
- **Inspector:** http://inspector.studenthub.local
- **Staff:** http://staff.studenthub.local
- **Verification:** http://verification.studenthub.local
- **phpMyAdmin:** http://phpmyadmin.studenthub.local (optional - requires `docker-compose -f docker-compose.yaml -f docker-compose.phpmyadmin.yaml up -d`)

**Note:** Traefik dashboard is also available at http://localhost:8080

### Step 6: Initialize Application

```bash
# Enter app container
docker exec -it studenthub-backend-dev bash

# Run migrations (if needed)
./yii migrate

# Initialize environment (if needed)
./init --env=Development --overwrite=All
```

## Production Deployment

### Railway Deployment

1. **Connect Repository:**
   - Go to Railway dashboard
   - New Project → Deploy from GitHub
   - Select repository

2. **Configure Service:**
   - Set `RAILWAY_DOCKERFILE_PATH` environment variable:
     - Dev: `./Dockerfile-nginx-dev-railway`
     - Prod: `./Dockerfile-nginx-railway`

3. **Add Services:**
   - MySQL service
   - Redis service
   - Link to app service

4. **Configure Domains:**
   - Railway automatically provides domains
   - Or add custom domains in settings

### Using Pre-built Images (GHCR)

1. **In Railway Service:**
   - Settings → Source → Use Docker image
   - Image: `ghcr.io/owner/repo/backend-railway-prod:latest`

2. **Add Secret:**
   - `GITHUB_TOKEN` - For private repos

3. **Deploy:**
   - Railway will pull and deploy image

## Common Commands

### Docker Compose

```bash
# Start services (default dev environment)
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f

# Rebuild and restart
docker-compose up -d --build

# Stop and remove volumes
docker-compose down -v
```

### Container Management

```bash
# Enter app container
docker exec -it studenthub-backend-dev bash

# View container logs
docker logs studenthub-backend-dev

# Restart container
docker restart studenthub-backend-dev

# View running containers
docker ps
```

### Database Access

```bash
# MySQL CLI
docker exec -it $(docker-compose ps -q mysql) mysql -u studenthubuser -pstudenthub studenthub

# Or via docker-compose
docker-compose exec mysql mysql -u studenthubuser -pstudenthub studenthub
```

### Redis Access

```bash
# Redis CLI
docker exec -it $(docker-compose ps -q redis) redis-cli
```

## Troubleshooting

### Services Not Starting

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs

# Check specific service
docker-compose logs app
```

### Port Conflicts

```bash
# Check what's using a port
# Linux
sudo lsof -i :80
# macOS
lsof -i :80
# Windows
netstat -ano | findstr :80
```

### DNS Issues

```bash
# Flush DNS cache
# Linux
sudo systemd-resolve --flush-caches
# macOS
sudo dscacheutil -flushcache
sudo killall -HUP mDNSResponder
# Windows
ipconfig /flushdns
```

### Permission Issues

```bash
# Fix file permissions
sudo chown -R $USER:$USER .
chmod -R 775 .
```

## Development Workflow

1. **Make code changes** in your editor
2. **Changes are live** (volume mount)
3. **Test locally** via domain names
4. **Commit and push** to trigger CI/CD
5. **Deploy** to Railway (automatic or manual)

## Next Steps

- Read [Traefik Setup Guide](./traefik-setup.md)
- Read [GitHub Actions Documentation](./github-actions-docker.md)
- Read [Architecture Overview](./architecture-overview.md)

