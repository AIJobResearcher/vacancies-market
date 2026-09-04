.PHONY: build up down exec logs test test-unit test-feature test-integration \
        test-coverage test-coverage-integration db-test-prepare \
        test-phpstan test-psalm test-phpcs test-phpcbf test-deptrac test-static

# ===== Infrastructure =====

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

# ===== Main testing command (runs everything) =====

# Runs all test suites and all static analysis tools.
# Integration tests require the test database to be prepared (see db-test-prepare).
test: test-unit test-feature test-integration test-phpstan test-psalm test-phpcs test-deptrac test-bdd
	@echo "✅ All tests and static analysis checks passed"

# ===== Individual Test Suites =====

test-unit:
	docker-compose exec app php artisan test --testsuite=Unit

test-feature:
	docker-compose exec app php artisan test --testsuite=Feature

test-integration:
	docker-compose exec -e DB_CONNECTION=pgsql -e DB_DATABASE=vacancies_market_test app \
		php artisan test --testsuite=Integration --configuration=phpunit.integration.xml

# ===== Code Coverage (optional) =====

test-coverage:
	docker-compose exec app php artisan test --coverage-html=coverage

test-coverage-integration:
	docker-compose exec -e DB_CONNECTION=pgsql -e DB_DATABASE=vacancies_market_test app \
		php artisan test --testsuite=Integration --configuration=phpunit.integration.xml --coverage-html=coverage-integration

# ===== Test Database Preparation =====

db-test-prepare:
	@echo "Creating test database..."
	docker-compose exec postgres psql -U vacancies_user -c "CREATE DATABASE vacancies_market_test;" 2>/dev/null || true
	@echo "Running migrations on test database..."
	docker-compose exec -e DB_CONNECTION=pgsql -e DB_DATABASE=vacancies_market_test app \
		php artisan migrate --force

# ===== Static Analysis Tools (prefix test-*) =====

test-phpstan:
	docker-compose exec app vendor/bin/phpstan analyse --memory-limit=2G

test-psalm:
	docker-compose exec app vendor/bin/psalm --shepherd --stats

test-phpcs:
	docker-compose exec app vendor/bin/phpcs --standard=phpcs.xml.dist

test-phpcs-fix:
	docker-compose exec app vendor/bin/phpcbf --standard=phpcs.xml.dist

test-deptrac:
	docker-compose exec app vendor/bin/deptrac analyse

# Run all static analysis tools together (optional convenience)
test-static: test-phpstan test-psalm test-phpcs test-deptrac
	@echo "✅ All static analysis checks passed"

# Run BDD tests (Codeception Gherkin)
test-bdd:
	docker-compose exec app vendor/bin/codecept run Acceptance docs/features/vacancies-market/managing_vacancies.feature