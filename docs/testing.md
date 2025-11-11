# Testing Guide

This project follows **Test-Driven Development (TDD)** using [Codeception](https://codeception.com/) as the testing framework.

## Test Status

✅ **Tests are configured and discoverable:**
- Codeception is installed and working
- Test files are properly structured across all modules
- Fixtures are in place with data files
- 80+ unit tests found in common module alone
- Functional tests exist for API endpoints

⚠️ **Database configuration needed:**
- Tests require database connection configuration
- Test databases need to be set up (see Setup section below)

## Quick Start

### Run All Tests

```bash
./run-tests.sh
```

Or manually:

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run"
```

### Run Tests for a Specific Module

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run common"
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run staff"
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run admin"
```

### Run a Specific Test Suite

```bash
# Unit tests only
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run common:unit"

# Functional tests only
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run staff:functional"
```

### Run a Specific Test File

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run common/tests/unit/models/CandidateTest.php"
```

### Run a Specific Test Method

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run common/tests/unit/models/CandidateTest.php:testValidations"
```

## Setup

### Prerequisites

1. **Docker containers running:**
   ```bash
   docker-compose up -d
   ```

2. **Test databases created:**
   The test databases should be created automatically, but verify:
   ```bash
   docker-compose exec mysql mysql -u root -p12345 -e "SHOW DATABASES LIKE '%test%';"
   ```
   
   Expected databases:
   - `studenthub_test` (or `payroll_test` depending on config)
   - `wallet_test`

3. **Database configuration:**
   The test configuration files are already set up:
   - `common/config/unit-test-local.php` - for unit tests (uses `mysql` host and `studenthub_test` database)
   - `common/config/test-local.php` - for functional tests
   
   Both use `mysql` as the host (Docker service name) and `studenthub_test` database.

4. **Grant database permissions:**
   ```bash
   docker-compose exec mysql mysql -u root -p12345 -e "GRANT ALL PRIVILEGES ON studenthub_test.* TO 'studenthubuser'@'%'; FLUSH PRIVILEGES;"
   docker-compose exec mysql-wallet mysql -u root -p12345 -e "GRANT ALL PRIVILEGES ON wallet_test.* TO 'studenthubuser'@'%'; FLUSH PRIVILEGES;"
   ```

5. **Copy database schema to test database:**
   ```bash
   docker-compose exec mysql bash -c "mysqldump -u root -p12345 --no-data studenthub 2>/dev/null | mysql -u root -p12345 studenthub_test 2>/dev/null"
   ```

6. **Load fixtures (optional, tests load their own fixtures):**
   ```bash
   docker-compose exec admin bash -c "cd /app && ./yii_test fixture/load '*' --interactive=0"
   ```

## Test Structure

### Unit Tests

Located in `tests/unit/` directories. Test individual models and components.

**Example:** `common/tests/unit/models/CandidateTest.php`

```php
class CandidateTest extends \Codeception\Test\Unit
{
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidates' => CandidateFixture::class,
            'country' => CountryFixture::class,
        ];
    }

    public function testValidations()
    {
        $candidate = new Candidate;
        $this->assertFalse($candidate->validate(['candidate_email']));
    }
}
```

### Functional Tests

Located in `tests/functional/` directories. Test API endpoints and integration.

**Example:** `staff/tests/functional/AuthCest.php`

```php
class AuthCest
{
    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::class
        ];
    }

    public function tryToLogin(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($staff->staff_email, '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK);
    }
}
```

## Fixtures

Fixtures provide test data. Located in `common/fixtures/`:

- **Fixture classes:** `common/fixtures/CandidateFixture.php` - Define model and dependencies
- **Data files:** `common/fixtures/data/candidate.php` - Contain test data arrays
- **Templates:** `common/fixtures/templates/` - Reusable data structures

## Common Commands

### List Available Tests

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run --no-colors"
```

### Run with Verbose Output

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run -vvv"
```

### Generate HTML Report

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run --html report.html"
```

### Run Tests Matching a Pattern

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run --grep 'login'"
```

### Stop on First Failure

```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run --fail-fast"
```

## Development Workflow (TDD)

1. **Write the test first** - Describe desired behavior
2. **Run the test** - It should fail (red)
3. **Implement the feature** - Write minimum code to pass
4. **Run the test again** - It should pass (green)
5. **Refactor** - Improve code while keeping tests green
6. **Repeat**

## Troubleshooting

### Database Connection Errors

**Error:** `SQLSTATE[HY000] [2002] No such file or directory`

**Solution:**
- Verify test database configuration in `common/config/test-local.php`
- Use `mysql` as host when running in Docker (not `localhost`)
- Ensure test databases exist
- Check database credentials

### Tests Not Found

**Solution:**
- Verify you're in the correct directory (`/app` in container)
- Check that test files exist in `tests/unit/` or `tests/functional/`
- Ensure Codeception is installed: `composer install`

### Fixtures Not Loading

**Solution:**
- Verify fixture classes extend `ActiveFixture`
- Check data files exist in `common/fixtures/data/`
- Ensure fixture dependencies are satisfied
- Run migrations and load fixtures (see Setup section)

### Permission Errors

**Solution:**
```bash
docker-compose exec admin bash -c "chmod -R 777 /app/tests/_output"
```

## Test Modules

Tests are organized by application module:

- `common` - Shared models and components (80+ unit tests)
- `admin` - Admin API endpoints
- `candidate` - Candidate API endpoints  
- `company` - Company API endpoints
- `staff` - Staff API endpoints
- `manager` - Manager API endpoints
- `inspector` - Inspector API endpoints
- `status` - Status API endpoints
- `verification` - Verification API endpoints

Each module can be tested independently or as part of the full suite.

## Additional Resources

- [Codeception Documentation](https://codeception.com/docs)
- [Yii2 Testing Guide](https://www.yiiframework.com/doc/guide/2.0/en/test-overview)
- [Codeception Yii2 Module](https://codeception.com/docs/modules/Yii2)

