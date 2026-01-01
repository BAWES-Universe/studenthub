# Environment Variables Migration

## Overview

All hardcoded keys, passwords, and configuration values have been extracted from config files into environment variables using `.env` files. This improves security and makes configuration management easier.

## Changes Made

### 1. Added Dotenv Package
- **Package:** `vlucas/phpdotenv` (^5.5)
- **Purpose:** Load `.env` files into PHP environment variables
- **Location:** `composer.json`

### 2. Environment Variable Loader
- **File:** `common/config/bootstrap.php`
- **Functionality:**
  - Loads root `.env` file if it exists
  - Loads environment-specific `.env` file (`environments/{dev|prod}/.env`) if it exists
  - Loads before config files are processed

### 3. Updated Config Files

All config files now use `getenv()` with fallback values:

#### `common/config/main.php`
- AWS credentials (temporary bucket, ID extractor)
- Google Maps API key
- ReCaptcha secret
- Jira credentials
- Algolia credentials
- IPStack key
- Cloudinary credentials
- Slack webhook URL

#### `environments/dev/common/config/main-local.php`
- Database credentials (main and wallet)
- Wallet Manager API key and endpoint
- Yeaster API endpoint
- Xero credentials
- Mailer configuration
- URL managers (Staff, Candidate, Verification)
- Event Manager
- MediaConvert configuration
- Resource Manager (S3)
- Sentry DSN and environment

#### `environments/prod/common/config/main-local.php`
- Database credentials (main and wallet, including slave)
- Wallet Manager API key
- Redis configuration
- Mailer (SMTP) configuration
- Xero credentials
- MediaConvert configuration
- Resource Manager (S3)
- Yeaster API endpoint
- URL managers
- Sentry DSN and environment

## Environment Variable Structure

### Root `.env`
- Shared configuration across all environments
- Common API keys and settings

### `environments/dev/.env`
- Development-specific overrides
- Local database credentials
- Dev API endpoints

### `environments/prod/.env`
- Production-specific overrides
- Production database credentials
- Production API endpoints

## Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Create Environment Files

**Root `.env` (for shared config):**
```bash
cp .env.template .env
# Edit .env and fill in shared values
```

**Development:**
```bash
cp .env.template environments/dev/.env
# Edit environments/dev/.env and fill in dev-specific values
```

**Production:**
```bash
cp .env.template environments/prod/.env
# Edit environments/prod/.env and fill in prod-specific values
```

### 3. Fill in Values

Use `.env.template` as a guide. All required variables are documented there.

**Important:** Never commit `.env` files (already in `.gitignore`)

## Migration from Hardcoded Values

### Before (Hardcoded)
```php
'db' => [
    'dsn' => 'mysql:host=mysql;dbname=studenthub',
    'username' => 'studenthubuser',
    'password' => '12345',
],
```

### After (Environment Variables)
```php
'db' => [
    'dsn' => 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASSWORD'),
],
```

## Environment Variable Reference

See `.env.template` for complete list of all environment variables.

### Key Categories:
- **Database:** `DB_*`, `WALLET_DB_*`
- **Redis:** `REDIS_*`
- **Email:** `SMTP_*`, `MAILER_*`
- **AWS:** `AWS_*`
- **APIs:** `GOOGLE_MAPS_API_KEY`, `RECAPTCHA_SECRET_KEY`, etc.
- **Application:** `WALLET_*`, `XERO_*`, `YEASTER_*`
- **URLs:** `STAFF_API_BASE_URL`, `CANDIDATE_API_BASE_URL`, etc.

## Fallback Values

All config files use the `?:` operator to provide fallback values:
```php
getenv('DB_HOST') ?: 'mysql'  // Falls back to 'mysql' if not set
```

This ensures the application works even if some environment variables are missing (though they should be set for production).

## Security Benefits

1. **No Secrets in Code:** All sensitive values are in `.env` files (gitignored)
2. **Environment-Specific:** Different values for dev/prod without code changes
3. **Easy Rotation:** Change secrets without touching code
4. **CI/CD Friendly:** Can inject env vars from secrets management

## Docker Integration

Environment variables can be passed to Docker containers:

**docker-compose.yaml:**
```yaml
services:
  app:
    env_file:
      - .env
      - environments/dev/.env
    environment:
      - DB_HOST=mysql
```

**Or via environment:**
```yaml
services:
  app:
    environment:
      - DB_HOST=${DB_HOST}
      - DB_NAME=${DB_NAME}
```

## Railway Integration

Railway automatically loads `.env` files. You can also set environment variables in Railway dashboard:
- Settings → Variables
- Add each variable or upload `.env` file

## Verification

To verify environment variables are loaded:

```php
// In any PHP file
var_dump(getenv('DB_HOST'));
var_dump(getenv('DB_NAME'));
```

Or use Yii2 console:
```bash
./yii shell
>>> getenv('DB_HOST')
```

## Troubleshooting

### Variables Not Loading

1. **Check file exists:**
   ```bash
   ls -la .env
   ls -la environments/dev/.env
   ```

2. **Check bootstrap.php loads dotenv:**
   - Should be in `common/config/bootstrap.php`
   - Should load before config files

3. **Check composer autoload:**
   ```bash
   composer dump-autoload
   ```

4. **Check file permissions:**
   ```bash
   chmod 644 .env
   ```

### Missing Variables

If a variable is missing, the fallback value will be used. Check logs for warnings about missing required variables.

### Production Deployment

1. **Create `.env` file on server:**
   ```bash
   cp .env.template .env
   # Edit with production values
   ```

2. **Or use environment variables:**
   - Set in Docker/Railway/etc.
   - No `.env` file needed

3. **Verify:**
   - Check application logs
   - Test database connection
   - Test API integrations

## Files Changed

- ✅ `composer.json` - Added vlucas/phpdotenv
- ✅ `common/config/bootstrap.php` - Added .env loader
- ✅ `common/config/main.php` - Uses env vars
- ✅ `environments/dev/common/config/main-local.php` - Uses env vars
- ✅ `environments/prod/common/config/main-local.php` - Uses env vars
- ✅ `.env.template` - Updated with all variables

## Next Steps

1. **Create `.env` files:**
   - Copy `.env.template` to `.env`
   - Copy to `environments/dev/.env` and `environments/prod/.env`
   - Fill in actual values

2. **Test locally:**
   - Run `composer install`
   - Verify app starts correctly
   - Test database connection
   - Test API integrations

3. **Update deployment:**
   - Add `.env` files to deployment process
   - Or configure environment variables in deployment platform
   - Test on staging first

4. **Remove old secrets:**
   - After verifying everything works
   - Consider rotating any exposed secrets

## References

- [PHP Dotenv Documentation](https://github.com/vlucas/phpdotenv)
- [Yii2 Configuration](https://www.yiiframework.com/doc/guide/2.0/en/concept-configurations)
- [12-Factor App: Config](https://12factor.net/config)

