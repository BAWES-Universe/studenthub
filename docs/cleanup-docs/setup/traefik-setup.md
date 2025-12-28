# Traefik Setup for Local Development

## Overview

Traefik replaces port-based routing in local development, allowing you to access all services via clean domain names instead of remembering port numbers.

## Architecture

```
Traefik (Port 80) 
  ├── admin.studenthub.local → Admin App (Port 21080)
  ├── candidate.studenthub.local → Candidate App (Port 22080)
  ├── company.studenthub.local → Company App (Port 23080)
  ├── inspector.studenthub.local → Inspector App (Port 24080)
  ├── staff.studenthub.local → Staff App (Port 25080)
  └── verification.studenthub.local → Verification App (Port 26080)
```

## Prerequisites

1. Docker and Docker Compose installed
2. Access to modify `/etc/hosts` file (or equivalent on Windows)

## Setup Instructions

### 1. Update /etc/hosts File

Add the following entries to your `/etc/hosts` file:

**Linux/macOS:**
```bash
sudo nano /etc/hosts
```

**Windows:**
```
C:\Windows\System32\drivers\etc\hosts
```

Add these lines:
```
127.0.0.1 admin.studenthub.local
127.0.0.1 candidate.studenthub.local
127.0.0.1 company.studenthub.local
127.0.0.1 inspector.studenthub.local
127.0.0.1 staff.studenthub.local
127.0.0.1 verification.studenthub.local
```

### 2. Start Services

```bash
docker-compose up -d
```

### 3. Access Services

Once containers are running, access services via:

- **Admin:** http://admin.studenthub.local
- **Candidate:** http://candidate.studenthub.local
- **Company:** http://company.studenthub.local
- **Inspector:** http://inspector.studenthub.local
- **Staff:** http://staff.studenthub.local
- **Verification:** http://verification.studenthub.local
- **Traefik Dashboard:** http://localhost:8080

### 4. Direct Port Access (Still Available)

If you need direct port access, the original ports are still mapped:
- Admin: http://localhost:21080
- Candidate: http://localhost:22080
- Company: http://localhost:23080
- Inspector: http://localhost:24080
- Staff: http://localhost:25080
- Verification: http://localhost:26080

## Configuration Files

### Traefik Configuration
- **Location:** `traefik/traefik.yml`
- **Purpose:** Static Traefik configuration
- **Key Settings:**
  - Dashboard enabled on port 8080
  - Docker provider enabled
  - Network: `studenthub-network`

### Docker Compose Labels
Labels in `docker-compose.yaml` configure routing:
- `traefik.enable=true` - Enables Traefik for the service
- `traefik.http.routers.*.rule` - Host-based routing rules
- `traefik.http.services.*.loadbalancer.server.port` - Backend port

## Troubleshooting

### Services Not Accessible

1. **Check Traefik is running:**
   ```bash
   docker-compose ps traefik
   ```

2. **Check Traefik logs:**
   ```bash
   docker-compose logs traefik
   ```

3. **Verify /etc/hosts entries:**
   ```bash
   cat /etc/hosts | grep studenthub.local
   ```

4. **Check app container is running:**
   ```bash
   docker-compose ps app
   ```

### Traefik Dashboard Not Loading

- Ensure port 8080 is not in use by another service
- Check Traefik container logs for errors
- Verify `traefik.yml` configuration is correct

### DNS Resolution Issues

**Linux:**
```bash
sudo systemd-resolve --flush-caches
```

**macOS:**
```bash
sudo dscacheutil -flushcache
sudo killall -HUP mDNSResponder
```

**Windows:**
```powershell
ipconfig /flushdns
```

## Benefits

✅ **No Port Memorization** - Use domain names instead of ports  
✅ **Clean URLs** - Professional-looking local development URLs  
✅ **Easy Service Discovery** - See all routes in Traefik dashboard  
✅ **Automatic SSL** - Can add Let's Encrypt for HTTPS (optional)  
✅ **Service Health** - Monitor service status in dashboard  

## Migration Notes

- Port mappings are still available for backward compatibility
- Old port-based access (e.g., `localhost:21080`) still works
- Traefik is only for local development - Railway uses nginx routing

## Next Steps

- Consider adding HTTPS with self-signed certificates for local dev
- Add health checks for services
- Configure rate limiting if needed

