#!/bin/bash
# Initialize test databases for test-driven development
# This script creates the test database needed for running tests
# Run this inside the docker container or ensure you can connect to MySQL

DB_HOST="${DB_HOST:-mysql}"
DB_USER="${DB_USER:-studenthubuser}"
DB_PASSWORD="${DB_PASSWORD:-studenthub}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-studenthub}"

echo "Creating test database..."

# Use root user for database creation
# Set password via environment variable to avoid SSL issues
export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"
mysql -h"$DB_HOST" -uroot --skip-ssl <<EOF
-- Create main test database
CREATE DATABASE IF NOT EXISTS \`studenthub_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges to the application user
GRANT ALL PRIVILEGES ON \`studenthub_test\`.* TO '$DB_USER'@'%';

FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
    echo "Test database created successfully!"
    echo "  - studenthub_test"
else
    echo "Error creating test database. Make sure MySQL is running and credentials are correct."
    exit 1
fi

