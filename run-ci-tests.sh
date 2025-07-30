#!/bin/bash

# Script to run CI tests locally with coverage
set -e

echo "🔧 Setting up CI test environment..."

# Ensure coverage mode is enabled
export XDEBUG_MODE=coverage

# Use CI configuration
export APP_ENV=testing

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""
echo "🧪 Running Unit Tests with coverage..."
./vendor/bin/pest --testsuite=Unit --configuration=phpunit.ci.xml --coverage --min=70 --exclude-group=slow || {
    echo "❌ Unit tests failed or coverage below 70%"
    exit 1
}

echo ""
echo "🔐 Running Security Tests..."
./vendor/bin/pest --testsuite=Security --configuration=phpunit.ci.xml || {
    echo "❌ Security tests failed"
    exit 1
}

echo ""
echo "🎯 Running Feature Tests with coverage..."
./vendor/bin/pest --testsuite=Feature --configuration=phpunit.ci.xml --coverage --min=65 --exclude-group=slow || {
    echo "❌ Feature tests failed or coverage below 65%"
    exit 1
}

echo ""
echo "🔗 Running Integration Tests with coverage..."
./vendor/bin/pest --testsuite=Integration --configuration=phpunit.ci.xml --coverage --min=55 --exclude-group=slow || {
    echo "❌ Integration tests failed or coverage below 55%"
    exit 1
}

echo ""
echo "✅ All tests passed with required coverage!"