# Nginx Configuration Files

This directory contains nginx server configuration files for different deployment environments. Each file defines how nginx routes requests to the different Yii2 applications (admin, candidate, company, inspector, staff, verification).

## File Overview

| File | Used By | Environment | Key Differences |
|------|---------|------------|-----------------|
| `development.conf` | `Dockerfile-nginx-dev` | Local Development | Includes `.studenthub.local` domains for Traefik routing |
| `production.conf` | `Dockerfile-nginx-prod` | Self-Hosted Production | Production domains only, no IPv6 |
| `railway-dev.conf` | `Dockerfile-nginx-dev-railway` | Railway Development | IPv6 support, Railway dev domains |
| `railway-prod.conf` | `Dockerfile-nginx-railway` | Railway Production | IPv6 support, Railway prod domains |
| `php.conf` | All configs | Shared | PHP-FPM configuration, security rules |

## Detailed Differences

### 1. `development.conf` (Local Development)

**Used by:** `Dockerfile-nginx-dev`  
**Purpose:** Local development with Traefik routing

**Key Features:**
- Includes both production-like domains (`*.api.dev.studenthub.co`) and local domains (`*.studenthub.local`)
- Single IPv4 listener (port 80)
- Default server block returns 404 for unmatched hosts
- Works with Traefik for domain-based routing

**Server Names:**
- Admin: `admin.api.dev.studenthub.co admin.studenthub.local`
- Candidate: `student.api.dev.studenthub.co candidate.studenthub.local`
- Company: `employer.api.dev.studenthub.co company.studenthub.local`
- Inspector: `inspector.api.dev.studenthub.co inspector.studenthub.local`
- Staff: `staff.api.dev.studenthub.co staff.studenthub.local`
- Verification: `v.dev.studenthub.co verification.studenthub.local`

**When to modify:**
- Adding new local development domains
- Changing Traefik routing setup
- Testing new domain configurations locally

---

### 2. `production.conf` (Self-Hosted Production)

**Used by:** `Dockerfile-nginx-prod`  
**Purpose:** Self-hosted production deployments (not Railway)

**Key Features:**
- Production domains only (no `.dev` or `.local` suffixes)
- Single IPv4 listener (port 80)
- Default server block returns 404 for unmatched hosts
- Cleaner, production-focused configuration

**Server Names:**
- Admin: `admin.api.studenthub.co`
- Candidate: `student.api.studenthub.co`
- Company: `employer.api.studenthub.co`
- Inspector: `inspector.api.studenthub.co`
- Staff: `staff.api.studenthub.co`
- Verification: `v.studenthub.co`

**When to modify:**
- Deploying to new production domain
- Adding new production subdomains
- Self-hosted production environment changes

---

### 3. `railway-dev.conf` (Railway Development)

**Used by:** `Dockerfile-nginx-dev-railway`  
**Purpose:** Railway development/staging environment

**Key Features:**
- Full IPv6 support (`listen [::]:80`) on all server blocks
- Development domains (`*.dev.studenthub.co`)
- Two verification server blocks (both `verification.dev.studenthub.co` and `v.dev.studenthub.co`)
- Default server block has IPv6 support

**Server Names:**
- Admin: `admin.api.dev.studenthub.co`
- Candidate: `student.api.dev.studenthub.co`
- Company: `employer.api.dev.studenthub.co`
- Inspector: `inspector.api.dev.studenthub.co`
- Staff: `staff.api.dev.studenthub.co`
- Verification: `verification.dev.studenthub.co` and `v.dev.studenthub.co` (both routes to verification)

**When to modify:**
- Railway dev environment domain changes
- Adding new Railway dev services

**Note:** Railway automatically handles SSL/TLS termination, so these configs only need HTTP (port 80).

---

### 4. `railway-prod.conf` (Railway Production)

**Used by:** `Dockerfile-nginx-railway`  
**Purpose:** Railway production environment

**Key Features:**
- Full IPv6 support on all server blocks
- Production domains (no `.dev` suffix)
- Two verification server blocks (both `verification.studenthub.co` and `v.studenthub.co`)
- Consistent IPv6 configuration across all blocks
- Default server block has IPv6 support

**Server Names:**
- Admin: `admin.api.studenthub.co`
- Candidate: `student.api.studenthub.co`
- Company: `employer.api.studenthub.co`
- Inspector: `inspector.api.studenthub.co`
- Staff: `staff.api.studenthub.co`
- Verification: `verification.studenthub.co` and `v.studenthub.co` (both routes to verification)

**When to modify:**
- Railway production domain changes
- Adding new Railway production services
- Production environment updates

**Note:** Railway automatically handles SSL/TLS termination, so these configs only need HTTP (port 80).

---


### 5. `php.conf` (Shared Configuration)

**Used by:** All nginx config files (included via `include php.conf;`)  
**Purpose:** Shared PHP-FPM and security configuration

**Key Features:**
- PHP-FPM configuration (FastCGI)
- Security rules (deny access to sensitive directories)
- Static file handling
- Timeout settings
- Yii2-specific routing rules

**When to modify:**
- Changing PHP-FPM settings
- Adding/removing security rules
- Modifying static file handling
- Updating timeout values

**Note:** Changes to `php.conf` affect all environments.

---

## Which File to Modify for Each Environment

### Local Development
**File:** `development.conf`  
**Dockerfile:** `Dockerfile-nginx-dev`  
**When:** 
- Adding new local domains (e.g., `newapp.studenthub.local`)
- Changing Traefik routing
- Testing domain configurations

### Self-Hosted Production
**File:** `production.conf`  
**Dockerfile:** `Dockerfile-nginx-prod`  
**When:**
- Deploying to new production domain
- Adding production subdomains
- Self-hosted environment changes

### Railway Development
**File:** `railway-dev.conf`  
**Dockerfile:** `Dockerfile-nginx-dev-railway`  
**When:**
- Railway dev/staging domain changes
- Fixing IPv6 configuration
- Adding Railway dev services

### Railway Production
**File:** `railway-prod.conf`  
**Dockerfile:** `Dockerfile-nginx-railway`  
**When:**
- Railway production domain changes
- Adding Railway production services
- Production environment updates

### Shared PHP Configuration
**File:** `php.conf`  
**Affects:** All environments  
**When:**
- Changing PHP-FPM settings
- Updating security rules
- Modifying static file handling

---

## Common Modifications

### Adding a New Domain

1. **Identify the correct config file** based on your environment (see table above)
2. **Add a new server block:**
   ```nginx
   server {
       listen 80;
       listen [::]:80;  # Only for Railway configs
       server_name newdomain.com;
       
       include php.conf;
       
       root /var/www/html/app-name/web;
   }
   ```
3. **Rebuild the Docker image** if using a Dockerfile
4. **Restart the container**

### Changing an Existing Domain

1. **Find the server block** in the appropriate config file
2. **Update the `server_name` directive:**
   ```nginx
   server_name olddomain.com newdomain.com;
   ```
3. **Rebuild and restart** (if using Dockerfile)

### Adding IPv6 Support (Railway configs)

For Railway configs, ensure all server blocks have:
```nginx
listen 80;
listen [::]:80;
```

---

## Verification Server Blocks

Both Railway configs have **two server blocks** for verification:
- `verification.studenthub.co` / `verification.dev.studenthub.co`
- `v.studenthub.co` / `v.dev.studenthub.co`

Both route to `/var/www/html/verification/web`. This is intentional to support both the full domain and the short alias.

---

## Notes

1. **Railway handles SSL/TLS:** Railway configs only need HTTP (port 80) because Railway terminates SSL/TLS automatically.

2. **Traefik for local dev:** The `development.conf` includes `.studenthub.local` domains for Traefik routing. Traefik routes these domains to the nginx container on port 80.

3. **Default server block:** All configs have a default server block that returns 404 for unmatched hosts. This prevents nginx from serving the first server block for unknown domains.

4. **Shared php.conf:** All configs include `php.conf` which contains PHP-FPM settings, security rules, and static file handling. Changes to `php.conf` affect all environments.

5. **IPv6 support:** All Railway configs (`railway-dev.conf` and `railway-prod.conf`) have full IPv6 support on all server blocks for Railway's dual-stack networking.

---

## Quick Reference: Domain Mapping

| Application | Development Domain | Production Domain | Local Domain |
|-------------|-------------------|-------------------|--------------|
| Admin | `admin.api.dev.studenthub.co` | `admin.api.studenthub.co` | `admin.studenthub.local` |
| Candidate | `student.api.dev.studenthub.co` | `student.api.studenthub.co` | `candidate.studenthub.local` |
| Company | `employer.api.dev.studenthub.co` | `employer.api.studenthub.co` | `company.studenthub.local` |
| Inspector | `inspector.api.dev.studenthub.co` | `inspector.api.studenthub.co` | `inspector.studenthub.local` |
| Staff | `staff.api.dev.studenthub.co` | `staff.api.studenthub.co` | `staff.studenthub.local` |
| Verification | `v.dev.studenthub.co` | `v.studenthub.co` | `verification.studenthub.local` |

---

## Troubleshooting

### Nginx not routing correctly
1. Check that the correct config file is being used by the Dockerfile
2. Verify `server_name` matches your actual domain
3. Check nginx logs: `docker exec <container> tail -f /var/log/nginx/error.log`
4. Test nginx config: `docker exec <container> nginx -t`

### Domain not working
1. Verify the domain is in the correct config file for your environment
2. Check DNS/hosts file configuration
3. Ensure Traefik (for local dev) is routing correctly
4. Check that the server block exists and is not commented out

### Changes not taking effect
1. Rebuild the Docker image if using a Dockerfile
2. Restart the container: `docker-compose restart app`
3. Reload nginx config: `docker exec <container> nginx -s reload`
4. Check that the volume mount is working (for dev, config should be copied on startup)

