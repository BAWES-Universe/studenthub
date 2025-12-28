# Docker Compose File Renaming

## Changes Made

All docker-compose files have been renamed from `.yml` to `.yaml` extension, and the default file has been simplified.

### File Changes

| Old Name | New Name | Purpose |
|----------|----------|---------|
| `docker-compose-dev.yml` | `docker-compose.yaml` | **Default** - Local development |
| `docker-compose-prod.yml` | `docker-compose-prod.yaml` | Production (self-hosted) |
| `docker-compose.phpmyadmin.yml` | `docker-compose.phpmyadmin.yaml` | Optional phpMyAdmin |

## Why `.yaml`?

- More standard extension (YAML stands for "YAML Ain't Markup Language")
- Better IDE support in some editors
- Consistent with modern Docker Compose practices

## Why Default `docker-compose.yaml`?

- **Simpler commands:** Just `docker-compose up` instead of `docker-compose -f docker-compose-dev.yml up`
- **Standard practice:** Docker Compose looks for `docker-compose.yaml` or `docker-compose.yml` by default
- **Better DX:** Less typing, fewer mistakes

## Updated Commands

### Before
```bash
# Start dev
docker-compose -f docker-compose-dev.yml up -d

# Stop dev
docker-compose -f docker-compose-dev.yml down

# View logs
docker-compose -f docker-compose-dev.yml logs -f
```

### After
```bash
# Start dev (default)
docker-compose up -d

# Stop dev
docker-compose down

# View logs
docker-compose logs -f
```

### Production (unchanged)
```bash
# Still need -f for production
docker-compose -f docker-compose-prod.yaml up -d
```

## Migration

If you have existing containers running:

1. **Stop old containers:**
   ```bash
   docker-compose -f docker-compose-dev.yml down
   ```

2. **Start with new file:**
   ```bash
   docker-compose up -d
   ```

The containers and volumes remain the same - only the file name changed.

## Backward Compatibility

- Old `.yml` files have been removed
- All documentation updated
- GitHub Actions workflow updated to watch both `.yaml` and `.yml` (for transition period)

## Benefits

✅ **Simpler commands** - No `-f` flag for default dev environment  
✅ **Standard extension** - `.yaml` is more common  
✅ **Better DX** - Less typing, fewer errors  
✅ **Clearer intent** - Default file is obvious  

