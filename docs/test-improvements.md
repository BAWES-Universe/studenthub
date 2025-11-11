# Test Improvements Plan

## Current Status

✅ **Test Infrastructure Working:**
- 478 total tests across all modules
- 453 tests passing (95%)
- 21 errors remaining
- 4 failures remaining
- 1,131 assertions passing

## Remaining Issues

### Test Errors (21)
1. **File Upload Tests** - AWS S3 integration issues:
   - BrandTest: Set logo
   - CompanyCest: File uploads, logo updates
   - AccountCest: Photo uploads (civil photos, profile photos, resume)
   - TransferCest: Excel file creation

2. **Validation Errors:**
   - CandidateTest: Fixed assertion syntax issues
   - FulltimerTest: Crud operations

3. **API Tests:**
   - CompanyCest: Create/update company operations
   - FulltimerCest: Create/update operations

### Test Failures (4)
- CompanyCest: Delete company file
- Other functional test failures

## Next Steps

### Phase 1: Fix Remaining Test Errors

1. **Suppress AWS Deprecation Warnings**
   - ✅ Added environment variable in bootstrap
   - Test if warnings are suppressed

2. **Fix File Upload Tests**
   - Mock AWS S3 client for tests
   - Create test fixtures for file uploads
   - Configure test file paths

3. **Fix Validation Tests**
   - ✅ Fixed CandidateTest assertion syntax
   - Review FulltimerTest issues

### Phase 2: Code Coverage Setup

1. **Install Coverage Extension** ⚠️ **REQUIRED**
   ```bash
   # Xdebug or PCOV is not currently installed
   # Need to add to Dockerfile or install in container:
   # Option 1: Install PCOV (faster, lighter)
   docker-compose exec admin bash -c "pecl install pcov && docker-php-ext-enable pcov"
   
   # Option 2: Install Xdebug (more features, slower)
   docker-compose exec admin bash -c "pecl install xdebug && docker-php-ext-enable xdebug"
   ```

2. **Configure Codeception Coverage**
   - ✅ Added coverage configuration to codeception.yml
   - ✅ Configured include/exclude paths
   - ⚠️ Coverage driver needs to be installed first

3. **Generate Coverage Report** (after installing driver)
   ```bash
   docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run --coverage --coverage-html coverage/"
   ```

### Phase 3: Improve Coverage

1. **Identify Low Coverage Areas**
   - Generate coverage report
   - Identify untested code paths
   - Prioritize critical business logic

2. **Add Missing Tests**
   - Unit tests for components
   - Integration tests for API endpoints
   - Edge case testing

3. **Set Coverage Goals**
   - Aim for 70%+ overall coverage
   - 80%+ for critical business logic
   - 60%+ for utility classes

## Commands

### Run Tests with Coverage
```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run --coverage --coverage-html coverage/"
```

### Run Specific Module with Coverage
```bash
docker-compose exec admin bash -c "cd /app && ./vendor/bin/codecept run common --coverage --coverage-html coverage/common/"
```

### View Coverage Report
```bash
# Coverage HTML will be in coverage/ directory
# Open coverage/index.html in browser
```

## Notes

- AWS SDK deprecation warnings are suppressed via environment variable
- File upload tests need AWS S3 mocking or test configuration
- Code coverage requires Xdebug or PCOV extension
- Focus on fixing critical business logic tests first

