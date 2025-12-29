#!/bin/bash
# Run test suites for CI/CD and local test-driven development
# This script runs tests in a docker container with test databases configured

set -e

# Determine service name (try 'app' first, fallback to 'backend' for backward compatibility)
SERVICE_NAME="app"
if ! docker-compose ps "$SERVICE_NAME" &>/dev/null 2>&1; then
    SERVICE_NAME="backend"
fi

# Ensure test databases exist before running tests
echo "Ensuring test databases are initialized..."
docker-compose run --rm "$SERVICE_NAME" bash -c "
    if [ -f /var/www/html/init-test-databases.sh ]; then
        /var/www/html/init-test-databases.sh || echo 'Warning: Could not initialize test databases. They may already exist.'
    else
        echo 'Warning: init-test-databases.sh not found. Make sure test databases exist.'
    fi
"

# Run the test suite
echo "Running test suites..."
docker-compose run --rm "$SERVICE_NAME" vendor/bin/codecept run --fail-fast --html report-web.html
