#!/bin/bash

# StudentHub Deployment Script
# Usage: ./deploy.sh [local|dev|prod]
# If no parameters provided, shows interactive menu

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print colored messages
print_info() {
    echo -e "${CYAN}ℹ${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Function to show interactive menu
show_menu() {
    clear
    echo "========================================"
    echo "  StudentHub Deployment Script"
    echo "========================================"
    echo ""
    echo "Select deployment environment:"
    echo ""
    echo "1) Local Development (base, no fixtures)"
    echo "   - For existing dev environments"
    echo "   - No database clearing"
    echo "   - No demo data"
    echo ""
    echo "2) Local Development with Fixtures (fresh install)"
    echo "   - For new dev setups"
    echo "   - Loads demo/test data"
    echo "   - Fresh database"
    echo ""
    echo "3) Production Deployment"
    echo "   - Production configuration"
    echo "   - Persistent volumes"
    echo "   - Production environment"
    echo ""
    echo "4) Exit"
    echo ""
    read -p "Enter choice [1-4]: " choice
    echo ""
    
    case $choice in
        1) ENV="local" ;;
        2) ENV="dev" ;;
        3) ENV="prod" ;;
        4) 
            echo "Exiting..."
            exit 0
            ;;
        *)
            print_error "Invalid option. Please select 1-4."
            exit 1
            ;;
    esac
}

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
    print_success "Docker is running"
}

# Function to wait for service to be ready
wait_for_service() {
    local service=$1
    local max_attempts=60
    local attempt=0
    
    print_info "Waiting for $service to be ready..."
    
    # Get container name/id once to avoid repeated docker-compose calls
    local container_id=""
    
    while [ $attempt -lt $max_attempts ]; do
        # Get container ID (suppress stderr to avoid version warnings)
        if [ -z "$container_id" ]; then
            container_id=$(docker-compose ps -q "$service" 2>/dev/null | head -1)
        fi
        
        # Check if container exists and is running
        if [ -n "$container_id" ] && docker ps --no-trunc --format '{{.ID}}' 2>/dev/null | grep -q "^${container_id}$"; then
            # Additional health checks for specific services
            if [ "$service" = "mysql" ]; then
                # Wait for MySQL to accept connections
                # Use root password from environment or default
                local mysql_root_pwd="${MYSQL_ROOT_PASSWORD:-12345}"
                if docker exec "$container_id" mysqladmin ping -h localhost -uroot -p"$mysql_root_pwd" --silent 2>/dev/null; then
                    print_success "$service is ready"
                    return 0
                fi
            elif [ "$service" = "redis" ]; then
                # Check Redis is responding
                if docker exec "$container_id" redis-cli ping 2>/dev/null | grep -q "PONG"; then
                    print_success "$service is ready"
                    return 0
                fi
            elif [ "$service" = "meilisearch" ]; then
                # Meilisearch starts quickly - check if container has been running
                # Try to access health endpoint, or use time-based fallback
                local container_age=$(docker inspect --format='{{.State.StartedAt}}' "$container_id" 2>/dev/null)
                if [ -n "$container_age" ]; then
                    # Parse container start time (ISO 8601 format: 2025-11-13T18:35:12.812829831Z)
                    # Remove microseconds and Z, then parse
                    local clean_date=$(echo "$container_age" | sed 's/\.[0-9]*Z/Z/' | sed 's/Z$/+0000/')
                    local started_epoch=$(date -d "$clean_date" +%s 2>/dev/null || \
                                         date -d "$container_age" +%s 2>/dev/null || echo 0)
                    local now_epoch=$(date +%s)
                    local age_seconds=$((now_epoch - started_epoch))
                    
                    # If container has been running for at least 3 seconds, try health check
                    if [ $age_seconds -ge 3 ]; then
                        # Try to check health endpoint from within container using wget
                        if docker exec "$container_id" sh -c 'wget -q -O- http://localhost:7700/health 2>/dev/null' > /dev/null 2>&1; then
                            print_success "$service is ready"
                            return 0
                        fi
                        # If health check fails but container is running for 5+ seconds, assume ready
                        # (Meilisearch starts quickly and may not have wget/curl available)
                        if [ $age_seconds -ge 5 ]; then
                            print_success "$service is ready (container running)"
                            return 0
                        fi
                    fi
                else
                    # If we can't get container age, just check if it's been running for a while
                    # by checking if this is not the first attempt
                    if [ $attempt -ge 3 ]; then
                        print_success "$service is ready (container running)"
                        return 0
                    fi
                fi
            else
                # For other services, just check if container is up
                print_success "$service is ready"
                return 0
            fi
        else
            # Try to get container ID again in case it just started
            container_id=$(docker-compose ps -q "$service" 2>/dev/null | head -1)
        fi
        
        attempt=$((attempt + 1))
        # Show progress every 10 attempts (but not on first attempt)
        if [ $attempt -gt 1 ] && [ $((attempt % 10)) -eq 0 ]; then
            print_info "Still waiting for $service... (attempt $attempt/$max_attempts)"
        fi
        sleep 2
    done
    
    print_warning "$service may not be fully ready, continuing anyway..."
}

# Function to check if MySQL volume is corrupted
check_mysql_health() {
    local max_checks=3
    local check=0
    
    # First check if container is in a bad state (restarting or exited)
    local status=$(docker-compose ps mysql 2>/dev/null | grep mysql | awk '{print $4}')
    if echo "$status" | grep -qE "Restarting|Exited|Dead"; then
        # Check logs for corruption errors
        if docker-compose logs mysql 2>/dev/null | grep -qiE "corruption|InnoDB.*assertion|got signal"; then
            return 1  # Definitely corrupted
        fi
    fi
    
    # If container is up, check if it's responding
    while [ $check -lt $max_checks ]; do
        sleep 2
        local container_id=$(docker-compose ps -q mysql 2>/dev/null)
        if [ -n "$container_id" ]; then
            # Check if MySQL is actually responding
            # Use root password from environment or default
            local mysql_root_pwd="${MYSQL_ROOT_PASSWORD:-12345}"
            if docker exec "$container_id" mysqladmin ping -h localhost -uroot -p"$mysql_root_pwd" --silent 2>/dev/null; then
                return 0  # MySQL is healthy
            fi
        fi
        check=$((check + 1))
    done
    
    # If we get here and container is restarting/exited, it's likely corrupted
    status=$(docker-compose ps mysql 2>/dev/null | grep mysql | awk '{print $4}')
    if echo "$status" | grep -qE "Restarting|Exited"; then
        return 1  # Likely corrupted
    fi
    
    return 0  # Probably just still initializing
}

# Function to deploy local environment
deploy_local() {
    print_info "Deploying Local Development environment (no fixtures)..."
    
    # Init services run automatically and are idempotent (skip if already done)
    docker-compose up -d --build
    
    print_info "Waiting for services to start..."
    sleep 15  # Give MySQL time to start initializing
    
    # Check MySQL health - if it's crashing, offer to reset volume
    # Wait a bit longer before checking to avoid false positives during initialization
    sleep 5
    if ! check_mysql_health; then
        print_error "MySQL appears to be crashing. This is likely due to volume corruption."
        echo ""
        print_info "To fix this, you can reset the MySQL volume:"
        echo "  1. docker-compose down"
        echo "  2. docker volume rm studenthub_mysql-data"
        echo "  3. Run ./deploy.sh again"
        echo ""
        read -p "Would you like to reset the MySQL volume now? (y/N): " reset_volume
        if [ "$reset_volume" = "y" ] || [ "$reset_volume" = "Y" ]; then
            print_info "Stopping services and removing MySQL volume..."
            docker-compose down
            docker volume rm studenthub_mysql-data 2>/dev/null || true
            print_info "Restarting services with fresh MySQL volume..."
            docker-compose up -d --build
            sleep 15  # Give MySQL more time to initialize fresh database
        else
            print_warning "Continuing anyway, but MySQL may not work properly."
        fi
    fi
    
    wait_for_service "mysql"
    wait_for_service "redis"
    wait_for_service "meilisearch"
    
    print_info "Checking for new database migrations..."
    # Yii2 migrate is idempotent - only applies NEW migrations that haven't been run yet
    # Migration history is stored in the database, so existing migrations won't re-run
    # Note: MySQL data persists via docker volume, so migration history survives docker-compose down
    docker-compose exec -T admin ./yii migrate --interactive=0 || print_warning "Migrations may have failed, check logs"
    # Skip test migrations - they should be run separately for testing and may fail in Docker
    
    print_info "Syncing Meilisearch indexes..."
    docker-compose exec -T admin ./yii meilisearch/sync || print_warning "Meilisearch sync may have failed, check logs"
    
    print_success "Local development environment deployed!"
    show_status "local"
}

# Function to deploy dev environment with fixtures
deploy_dev() {
    print_info "Deploying Development environment with fixtures..."
    
    # Init services run automatically and are idempotent (skip if already done)
    docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
    
    print_info "Waiting for services to start..."
    sleep 15  # Give MySQL time to start initializing
    
    # Check MySQL health - if it's crashing, offer to reset volume
    # Wait a bit longer before checking to avoid false positives during initialization
    sleep 5
    if ! check_mysql_health; then
        print_error "MySQL appears to be crashing. This is likely due to volume corruption."
        echo ""
        print_info "To fix this, you can reset the MySQL volume:"
        echo "  1. docker-compose down"
        echo "  2. docker volume rm studenthub_mysql-data"
        echo "  3. Run ./deploy.sh again"
        echo ""
        read -p "Would you like to reset the MySQL volume now? (y/N): " reset_volume
        if [ "$reset_volume" = "y" ] || [ "$reset_volume" = "Y" ]; then
            print_info "Stopping services and removing MySQL volume..."
            docker-compose -f docker-compose.yml -f docker-compose.dev.yml down
            docker volume rm studenthub_mysql-data 2>/dev/null || true
            print_info "Restarting services with fresh MySQL volume..."
            docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
            sleep 15  # Give MySQL more time to initialize fresh database
        else
            print_warning "Continuing anyway, but MySQL may not work properly."
        fi
    fi
    
    wait_for_service "mysql"
    wait_for_service "redis"
    wait_for_service "meilisearch"
    
    print_info "Waiting for fixtures to load..."
    # Wait for load-fixture service to complete
    while docker-compose -f docker-compose.yml -f docker-compose.dev.yml ps | grep -q "load-fixture.*Up"; do
        sleep 2
    done
    
    print_info "Checking for new database migrations..."
    # Yii2 migrate is idempotent - only applies NEW migrations that haven't been run yet
    # Migration history is stored in the database, so existing migrations won't re-run
    # Note: MySQL data persists via docker volume, so migration history survives docker-compose down
    docker-compose exec -T admin ./yii migrate --interactive=0 || print_warning "Migrations may have failed, check logs"
    # Skip test migrations - they should be run separately for testing and may fail in Docker
    
    print_info "Syncing Meilisearch indexes..."
    docker-compose exec -T admin ./yii meilisearch/sync || print_warning "Meilisearch sync may have failed, check logs"
    
    print_success "Development environment with fixtures deployed!"
    show_status "dev"
}

# Function to deploy production environment
deploy_prod() {
    print_info "Deploying Production environment..."
    
    # Check for required environment variables
    if [ -z "$MYSQL_ROOT_PASSWORD" ] || [ -z "$MEILI_MASTER_KEY" ]; then
        print_error "Production deployment requires environment variables:"
        print_error "  - MYSQL_ROOT_PASSWORD"
        print_error "  - MEILI_MASTER_KEY"
        print_error "  - MYSQL_DATABASE"
        print_error "  - MYSQL_USER"
        print_error "  - MYSQL_PASSWORD"
        print_info "Please set these in your .env file or export them before running."
        exit 1
    fi
    
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
    
    print_info "Waiting for services to start..."
    sleep 10
    
    wait_for_service "mysql"
    wait_for_service "redis"
    wait_for_service "meilisearch"
    
    print_info "Checking for new database migrations..."
    # Only run migrations if there are new ones (migrate command is idempotent)
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T admin ./yii migrate --interactive=0 || print_warning "Migrations may have failed, check logs"
    
    print_info "Syncing Meilisearch indexes..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T admin ./yii meilisearch/sync || print_warning "Meilisearch sync may have failed, check logs"
    
    print_success "Production environment deployed!"
    show_status "prod"
}

# Function to show deployment status
show_status() {
    local env=$1
    echo ""
    echo "========================================"
    echo "  Deployment Status"
    echo "========================================"
    echo ""
    
    if [ "$env" = "dev" ]; then
        docker-compose -f docker-compose.yml -f docker-compose.dev.yml ps
    elif [ "$env" = "prod" ]; then
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
    else
        docker-compose ps
    fi
    
    echo ""
    echo "Service URLs:"
    echo "  - Admin: http://admin.studenthub.local"
    echo "  - Staff: http://staff.studenthub.local"
    echo "  - Company: http://company.studenthub.local"
    echo "  - Candidate: http://candidate.studenthub.local"
    echo "  - Manager: http://manager.studenthub.local"
    echo "  - Inspector: http://inspector.studenthub.local"
    echo "  - Verification: http://verification.studenthub.local"
    echo "  - Traefik Dashboard: http://traefik.studenthub.local"
    echo "  - phpMyAdmin: http://phpmyadmin.studenthub.local"
    echo ""
    echo "Next steps:"
    echo "  - Check service logs: docker-compose logs [service-name]"
    echo "  - Stop services: docker-compose down"
    echo "  - View all services: docker-compose ps"
    echo ""
}

# Main execution
main() {
    # Check Docker first
    check_docker
    
    # Determine environment
    if [ $# -eq 0 ]; then
        # No parameters - show interactive menu
        show_menu
    else
        # Direct parameter mode (for automation/Railway)
        ENV=$1
        case $ENV in
            local|dev|prod)
                ;;
            *)
                print_error "Invalid environment: $ENV"
                echo "Usage: ./deploy.sh [local|dev|prod]"
                exit 1
                ;;
        esac
    fi
    
    # Deploy based on environment
    case $ENV in
        local)
            deploy_local
            ;;
        dev)
            deploy_dev
            ;;
        prod)
            deploy_prod
            ;;
        *)
            print_error "Unknown environment: $ENV"
            exit 1
            ;;
    esac
}

# Run main function
main "$@"

