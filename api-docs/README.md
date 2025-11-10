# API Documentation Service

This service provides self-hosted API documentation using Scalar, a modern and beautiful API documentation tool.

## Setup

1. **Generate OpenAPI specification:**
   ```bash
   docker-compose exec admin ./yii swagger/generate
   ```
   This scans all API controllers and generates `openapi.json` in the `api-docs/openapi/` directory.

2. **Start the documentation service:**
   ```bash
   docker-compose up -d api-docs
   ```

3. **Access the documentation:**
   - Visit `http://api-docs.studenthub.local` in your browser
   - Make sure `api-docs.studenthub.local` is in your `/etc/hosts` file:
     ```
     127.0.0.1 api-docs.studenthub.local
     ```

## Regenerating Documentation

After adding or modifying OpenAPI annotations in controllers, regenerate the spec:

```bash
docker-compose exec admin ./yii swagger/generate
docker-compose exec admin ./yii swagger/generate-modules
```

The documentation will automatically reflect changes after refreshing the browser.

## Rebuild api-docs to clear cache and see changes

```bash
docker-compose build api-docs && docker-compose up -d api-docs
```

## Adding Annotations

**Important:** OpenAPI documentation only includes endpoints that have `@OA\*` annotations. Currently, only 3 endpoints are documented (from AuthController). To document all API features:

1. Add `@OA\*` annotations to controller classes and action methods
2. See `candidate/modules/v1/controllers/AuthController.php` for examples
3. Regenerate the spec: `docker-compose exec admin ./yii swagger/generate`

**Example annotation:**
```php
/**
 * @OA\Get(
 *     path="/auth/login",
 *     summary="User login",
 *     @OA\Response(response=200, description="Success")
 * )
 */
public function actionLogin() { ... }
```

**Note:** The generator scans all controller directories but only documents annotated methods. Add annotations incrementally to build complete documentation.

## Structure

- `Dockerfile` - nginx-based container for serving documentation
- `nginx.conf` - nginx configuration
- `index.html` - Scalar API Reference embedded page
- `openapi/` - Directory for generated OpenAPI JSON files (gitignored)

