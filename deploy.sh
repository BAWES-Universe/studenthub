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
    local max_attempts=30
    local attempt=0
    
    print_info "Waiting for $service to be ready..."
    
    while [ $attempt -lt $max_attempts ]; do
        if docker-compose ps | grep -q "$service.*Up"; then
            # Additional check for MySQL
            if [ "$service" = "mysql" ]; then
                if docker-compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
                    print_success "$service is ready"
                    return 0
                fi
            else
                print_success "$service is ready"
                return 0
            fi
        fi
        attempt=$((attempt + 1))
        sleep 2
    done
    
    print_warning "$service may not be fully ready, continuing anyway..."
}

# Function to deploy local environment
deploy_local() {
    print_info "Deploying Local Development environment (no fixtures)..."
    
    docker-compose up -d --build
    
    print_info "Waiting for services to start..."
    sleep 10
    
    wait_for_service "mysql"
    wait_for_service "redis"
    wait_for_service "meilisearch"
    
    print_info "Running database migrations..."
    docker-compose exec -T admin ./yii migrate --interactive=0 || print_warning "Migrations may have failed, check logs"
    docker-compose exec -T admin ./yii_test migrate --interactive=0 || print_warning "Test migrations may have failed, check logs"
    
    print_info "Syncing Meilisearch indexes..."
    docker-compose exec -T admin ./yii meilisearch/sync || print_warning "Meilisearch sync may have failed, check logs"
    
    print_success "Local development environment deployed!"
    show_status "local"
}

# Function to deploy dev environment with fixtures
deploy_dev() {
    print_info "Deploying Development environment with fixtures..."
    
    docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
    
    print_info "Waiting for services to start..."
    sleep 10
    
    wait_for_service "mysql"
    wait_for_service "redis"
    wait_for_service "meilisearch"
    
    print_info "Waiting for fixtures to load..."
    # Wait for load-fixture service to complete
    while docker-compose -f docker-compose.yml -f docker-compose.dev.yml ps | grep -q "load-fixture.*Up"; do
        sleep 2
    done
    
    print_info "Running database migrations..."
    docker-compose exec -T admin ./yii migrate --interactive=0 || print_warning "Migrations may have failed, check logs"
    docker-compose exec -T admin ./yii_test migrate --interactive=0 || print_warning "Test migrations may have failed, check logs"
    
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
    
    print_info "Running database migrations..."
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

