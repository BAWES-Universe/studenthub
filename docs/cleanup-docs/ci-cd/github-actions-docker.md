# GitHub Actions Docker Build & Release

## Overview

GitHub Actions automatically builds and pushes Docker images to GitHub Container Registry (GHCR) on code pushes, releases, and manual triggers.

## Workflow Triggers

The workflow (`/.github/workflows/docker-build.yml`) triggers on:

1. **Push to main/develop** - Builds relevant images
2. **Pull Requests** - Builds images (without pushing) for testing
3. **Releases** - Builds production images with release tags
4. **Manual Dispatch** - Allows manual builds with custom tags

## Image Build Matrix

| Dockerfile | Tag | Branches |
|------------|-----|----------|
| `Dockerfile-nginx-prod` | `prod` | main, develop, releases |
| `Dockerfile-nginx-railway` | `railway-prod` | main, releases |
| `Dockerfile-nginx-dev-railway` | `railway-dev` | develop |

## Image Naming Convention

Images are pushed to: `ghcr.io/{owner}/{repo}/backend-{tag}`

Examples:
- `ghcr.io/plugnio/studenthub/backend-prod:latest`
- `ghcr.io/plugnio/studenthub/backend-railway-prod:v1.0.0`
- `ghcr.io/plugnio/studenthub/backend-railway-dev:dev`

## Tagging Strategy

### Automatic Tags

- **main branch:** `latest` + `{sha}`
- **develop branch:** `dev` + `{sha}`
- **releases:** `{release-tag}` (e.g., `v1.0.0`)
- **PRs:** `{sha}` (build only, no push)

### Manual Dispatch Tags

When using manual dispatch, you can specify:
- Custom tag name
- Which Dockerfile to build

## Usage

### Viewing Built Images

1. Go to your GitHub repository
2. Click "Packages" in the right sidebar
3. Find `backend-{tag}` packages
4. Click to view versions and pull commands

### Pulling Images

```bash
# Login to GHCR
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin

# Pull image
docker pull ghcr.io/plugnio/studenthub/backend-prod:latest
```

### Using in Railway

1. In Railway service settings, switch from "Build from source" to "Use Docker image"
2. Set image: `ghcr.io/plugnio/studenthub/backend-railway-prod:latest`
3. Add `GITHUB_TOKEN` secret for private repos

### Manual Build

1. Go to Actions tab in GitHub
2. Select "Build and Push Docker Images"
3. Click "Run workflow"
4. Select:
   - Branch
   - Dockerfile to build
   - Optional custom tag

## Workflow Configuration

### Permissions

The workflow requires:
- `contents: read` - To checkout code
- `packages: write` - To push images to GHCR

These are automatically granted via `GITHUB_TOKEN`.

### Caching

- Uses GitHub Actions cache for Docker layer caching
- Significantly speeds up subsequent builds
- Cache key based on Dockerfile content

### Build Arguments

Currently no build args, but can be added:
```yaml
build-args: |
  BUILD_ENV=production
  VERSION=${{ github.sha }}
```

## Monitoring

### View Workflow Runs

1. Go to Actions tab
2. Click on "Build and Push Docker Images"
3. View run history and logs

### Check Build Status

- Green checkmark = Success
- Red X = Failure
- Yellow circle = In progress

## Troubleshooting

### Build Failures

1. **Check workflow logs:**
   - Go to Actions → Failed run → Job → Step logs

2. **Common issues:**
   - Dockerfile syntax errors
   - Missing dependencies
   - Network issues during build

3. **Fix and retry:**
   - Fix the issue in code
   - Push to trigger new build
   - Or use manual dispatch

### Image Not Found

1. **Check image visibility:**
   - Private repos require authentication
   - Public repos are publicly accessible

2. **Verify image name:**
   ```bash
   # Correct format
   ghcr.io/owner/repo/backend-prod:latest
   ```

### Authentication Issues

For private repositories:
1. Create Personal Access Token (PAT) with `read:packages` scope
2. Use it instead of `GITHUB_TOKEN`:
   ```bash
   docker login ghcr.io -u USERNAME -p PAT
   ```

## Best Practices

1. **Tag Production Releases:**
   - Create GitHub releases for production deployments
   - Images automatically tagged with release version

2. **Test Before Production:**
   - Test images on develop branch first
   - Use PR builds to verify Dockerfile changes

3. **Monitor Image Sizes:**
   - Keep images lean
   - Use multi-stage builds
   - Remove unnecessary dependencies

4. **Security:**
   - Regularly update base images
   - Scan images for vulnerabilities
   - Use specific tags, not just `latest`

## Migration from AWS ECR

If migrating from AWS ECR:

1. **Update deployment scripts:**
   - Change image registry from ECR to GHCR
   - Update authentication method

2. **Update Railway:**
   - Switch to GHCR images
   - Add `GITHUB_TOKEN` secret

3. **Keep ECR (optional):**
   - Can maintain both registries
   - Use ECR for AWS deployments
   - Use GHCR for Railway

## Cost Considerations

- **GHCR:** Free for public repos, 500MB storage + 1GB bandwidth/month for private
- **GitHub Actions:** 2,000 free minutes/month (usually sufficient)
- **Total:** Effectively free for most use cases

## Next Steps

- [ ] Set up automated security scanning
- [ ] Configure image retention policies
- [ ] Add build notifications (Slack, email)
- [ ] Set up staging environment with GHCR images

