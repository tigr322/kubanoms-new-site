#!/bin/bash

# Production Deployment Script
# Usage: ./deploy.sh

set -e

echo "🚀 Starting production deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if .env.prod exists
if [ ! -f .env.prod ]; then
    print_error ".env.prod file not found!"
    echo "Please copy .env.prod.example to .env.prod and configure it."
    exit 1
fi

# Check if docker-compose.prod.yml exists
if [ ! -f docker-compose.prod.yml ]; then
    print_error "docker-compose.prod.yml not found!"
    exit 1
fi

# Backup current deployment if exists
if [ -d storage/app/backups ]; then
    print_status "Creating backup..."
    mkdir -p storage/app/backups
    tar -czf "storage/app/backups/backup-$(date +%Y%m%d-%H%M%S).tar.gz" \
        storage/app/uploads \
        database/database.sqlite 2>/dev/null || true
fi

# Stop existing services
print_status "Stopping existing services..."
docker-compose -f docker-compose.prod.yml down || true

# Pull latest images
print_status "Pulling latest images..."
docker-compose -f docker-compose.prod.yml pull

# Build application image
print_status "Building application image..."
docker-compose -f docker-compose.prod.yml build --no-cache app

# Start services
print_status "Starting services..."
docker-compose -f docker-compose.prod.yml up -d

# Wait for database to be ready
print_status "Waiting for database to be ready..."
sleep 30

# Run migrations
print_status "Running database migrations..."
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Cache configuration
print_status "Caching configuration..."
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

# Clear and warm up caches
print_status "Clearing and warming up caches..."
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec app php artisan queue:restart

# Check service health
print_status "Checking service health..."
sleep 10

if docker-compose -f docker-compose.prod.yml exec -T app curl -f http://localhost/health > /dev/null 2>&1; then
    print_status "✅ Application is healthy!"
else
    print_error "❌ Application health check failed!"
    docker-compose -f docker-compose.prod.yml logs app
    exit 1
fi

# Show service status
print_status "Service status:"
docker-compose -f docker-compose.prod.yml ps

print_status "🎉 Deployment completed successfully!"
echo ""
echo "📊 Access URLs:"
echo "  Application: http://localhost"
echo "  phpMyAdmin:  http://localhost:8080"
echo ""
echo "🔧 Useful commands:"
echo "  View logs:    docker-compose -f docker-compose.prod.yml logs -f app"
echo "  Stop services: docker-compose -f docker-compose.prod.yml down"
echo "  Restart app:  docker-compose -f docker-compose.prod.yml restart app"
