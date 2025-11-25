#!/bin/bash

# Script to run tests in Docker container

echo "Building Docker container..."
docker-compose build

echo "Starting Docker container..."
docker-compose up -d

echo "Updating dependencies for PHP 8.3..."
docker-compose exec app composer update --no-interaction

echo "Running tests..."
docker-compose exec app ./vendor/bin/phpunit

echo "Tests completed!"
echo ""
echo "To run tests again: docker-compose exec app ./vendor/bin/phpunit"
echo "To stop container: docker-compose down"
