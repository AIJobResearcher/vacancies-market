.PHONY: build up down exec logs test test-unit test-feature test-integration \
        test-coverage test-coverage-integration db-test-prepare

build:
	chmod +x deploy/deploy.sh
	./deploy/deploy.sh

up:
	docker-compose up -d

down:
	docker-compose down

exec:
	docker-compose exec app sh

logs:
	docker-compose logs -f

# === Testing targets ===

# Run all tests (Unit + Feature) using SQLite in-memory
test:
	docker-compose exec app php artisan test

# Run only Unit tests (fast, no database required)
test-unit:
	docker-compose exec app php artisan test --testsuite=Unit

# Run only Feature tests (may use SQLite or real DB)
test-feature:
	docker-compose exec app php artisan test --testsuite=Feature

# Run Integration tests against a real PostgreSQL database
# Prerequisite: containers are up and test database is created (see db-test-prepare)
test-integration:
	docker-compose exec -e DB_CONNECTION=pgsql -e DB_DATABASE=vacancies_market_test app \
		php artisan test --testsuite=Integration --configuration=phpunit.integration.xml

# Generate code coverage report for Unit and Feature tests (requires Xdebug/PCOV)
test-coverage:
	docker-compose exec app php artisan test --coverage-html=coverage

# Generate code coverage report for Integration tests
test-coverage-integration:
	docker-compose exec -e DB_CONNECTION=pgsql -e DB_DATABASE=vacancies_market_test app \
		php artisan test --testsuite=Integration --configuration=phpunit.integration.xml --coverage-html=coverage-integration

# Prepare the test database: create it and run migrations
# This should be run once before running integration tests
db-test-prepare:
	@echo "Creating test database..."
	docker-compose exec postgres psql -U vacancies_user -c "CREATE DATABASE vacancies_market_test;" 2>/dev/null || true
	@echo "Running migrations on test database..."
	docker-compose exec -e DB_CONNECTION=pgsql -e DB_DATABASE=vacancies_market_test app \
		php artisan migrate --force