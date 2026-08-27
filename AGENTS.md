---
title: "AGENTS Quick Guide"
summary: "Machine-friendly quick guide for AI agents: reading priority, commands, patterns, and rules for working with contracts and events."
tags: ["agents","quickstart","events","contracts"]
owners: ["subscribe.software.engineer@gmail.com"]
last_updated: "2026-06-19"
version: "1.2"
---

# AGENTS.md — Quick guide for AI agents (short)

This file helps AI agents (Copilot, Claude, Cursor, etc.) become productive quickly
in the AIJobResearcher (Vacancies‑market) repository. It contains reading priorities,
quick commands, core architectural patterns and step‑by‑step guidance for common tasks.

---

Priority checklist for an agent
- Read in order: `docs/README.md` → `docs/architecture-overview.md` → `docs/glossary.md`.
- Then: inspect `app/Application/`, `app/Domain/`, `app/Infrastructure/`, `routes/web.php`, `tests/`.
- For contract changes update `docs/asyncapi/events.yaml` or `docs/api/<service>/` and follow CI contract rules.

Quickstart commands (project root)

Local setup and build (see `composer.json` → scripts.setup):
```bash
composer run-script setup
# or manually
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php artisan migrate --force
npm install --ignore-scripts
npm run build
```

Development (dev):
```bash
composer run-script dev
make up       # docker-compose up -d
make exec     # docker-compose exec app sh
```

Tests:
```bash
composer test
php artisan test
vendor/bin/phpunit
```

What to read first (priority)
- `docs/README.md` — documentation index.
- `docs/architecture-overview.md` — high‑level architecture (Clean Architecture, EDA, Outbox, Idempotency).
- `docs/technical-requirements.md` — SLO/NFR and performance expectations.
- `docs/glossary.md` — ubiquitous language and definitions.
- `docs/asyncapi/events.yaml` — domain events specification (source of truth for events).
- `docs/api/` — OpenAPI specifications per service.

Architectural patterns and where to find them in the code
- Clean Architecture: Presentation → Application → Domain → Infrastructure
  - Presentation (HTTP): `app/Presentation/Http/Controllers/` (base: `Controller.php`)
  - Application (use cases / handlers / DTOs): `app/Application/` — subdirs `Commands/`, `Handlers/`, `DTOs/` exist but are **currently empty stubs** (next implementation step)
  - Domain (entities, value objects, events, repository interfaces): `app/Domain/`
  - Infrastructure (implementations, persistence, messaging, ACL): `app/Infrastructure/`
- Outbox pattern: used for reliable event publication (see ADR in `docs/adr/adr-011-outbox-pattern.md`).
- Idempotency: deduplication via `processed_events` table — consumers check `event_id` (see `docs/adr/adr-013-idempotency.md`).
- Search: OpenSearch indexing is done asynchronously via RabbitMQ events (see `docs/adr/adr-014-opensearch.md`).

Code style and conventions (PHP 8.5 / Laravel 13)
- **Type system**: Prefer union types (`int|string`), **readonly properties**, and strict typing. Use `declare(strict_types=1);` in new files.
- **PSR-12 formatting**: Run `vendor/bin/pint` before committing. Pint uses Laravel's PSR-12 preset by default.
- **Test files**: Unit tests extend `PHPUnit\Framework\TestCase`; feature/integration tests extend `Tests\TestCase` (which extends Laravel's `TestCase`).
- **Entity invariants**: Domain entities validate state in constructors (e.g., `Vacancy` requires non-empty `title` and `employerId`). See `app/Domain/Entities/Vacancy.php` for the pattern.

Implemented domain objects (as of 2026-06-19)
- Entities: `app/Domain/Entities/` — `Vacancy`, `Employer`, `Interviewer`, `Portal`
  - `Vacancy` enforces invariants (non-empty title/employerId) and increments `version` on each mutation (`close()`, `updateDescription()`, `updateRequirements()`, `reopen()`).
- Value objects: `app/Domain/ValueObjects/Salary.php`, `Contacts.php`
- Enums: `app/Domain/Enums/VacancyStatusEnum.php` (`OPEN`, `CLOSED`)
- Domain events: `app/Domain/Events/` — `VacancyImported`, `VacancyUpdated`, `VacancyClosed`, `InterviewerAssigned`, `ExternalPortalUnreachable`
- Repository interfaces: `app/Domain/Repositories/` — `VacancyRepositoryInterface`, `EmployerRepositoryInterface`, `InterviewerRepositoryInterface`, `PortalRepositoryInterface`
- Eloquent implementations: `app/Infrastructure/Persistence/` — one `*EloquentRepository` per interface; bindings registered in `app/Infrastructure/Providers/AppServiceProvider.php`
- Eloquent models (infrastructure, not domain): `app/Infrastructure/Models/`
- Infrastructure stubs (not yet implemented): `app/Infrastructure/Messaging/`, `app/Infrastructure/ACL/`, `app/Infrastructure/Search/`

Rules for working with events (concrete)
- When adding or changing an event: update `docs/asyncapi/events.yaml` and bump `event_version` for breaking changes.
- Add or update an ADR if the change has architectural impact (`docs/adr/`).
- Update the producer in `app/Infrastructure/Messaging/` and/or the handler in `app/Application/Handlers/`.
- Add tests: unit tests for business logic and integration/consumer tests for downstream processing.

Typical tasks — what to change and where
- Add a new handler for `ReplyCreated` event:
  1) update `docs/asyncapi/events.yaml` (event schema),
  2) implement `app/Application/Handlers/ReplyCreatedHandler.php`,
  3) if needed, add persistence implementation in `app/Infrastructure/Persistence/` and register binding in `app/Infrastructure/Providers/AppServiceProvider.php`,
  4) add unit tests in `tests/Unit/` and integration/consumer tests in `tests/Feature/`.
- Add a new API endpoint:
  1) add route in `routes/web.php`,
  2) create controller in `app/Presentation/Http/Controllers/`,
  3) place use case in `app/Application/`,
  4) update `docs/api/<service>/openapi.yaml` and ensure CI passes.
- Add a new repository binding:
  - Define interface in `app/Domain/Repositories/`, implement in `app/Infrastructure/Persistence/`, register singleton in `app/Infrastructure/Providers/AppServiceProvider.php`.

Quick safety and quality tips
- **Before PR**: Run `composer test` and `vendor/bin/pint` (format check/fix). See `./.github/copilot-instructions.md` section 2 for details.
- **Keep PRs small**: One change, one goal. Tests are mandatory for business logic; doc-only changes may skip tests.
- **Breaking changes**: For events, bump `event_version` in `docs/asyncapi/events.yaml`, add/update ADR, include migration plan or integration tests.
- **Local testing**: Use `make up && make exec` to run in Docker, then `composer test` inside the container.

Agent prompt templates (copy & adapt)
- "Add event VacancyClosed: update `docs/asyncapi/events.yaml`, create handler `app/Application/Handlers/VacancyClosedHandler.php`, add unit test `tests/Unit/VacancyClosedHandlerTest.php`."
- "Refactor method in `app/Domain/Entities/Vacancy.php` preserving backward compatibility; add tests for edge cases and invariants."

Source files and quick mapping
- Docs: `docs/` (index: `docs/README.md`). Use `docs/README.md` as the single documentation index (do not create a separate `docs/TOC.md`).
- Contracts: `docs/asyncapi/events.yaml` (source of truth for events); `docs/api/` folder structure is ready but OpenAPI specs per service (vacancies-market/, researcher-crm/, parsing-ai-connector/, knowledge-center/) are not yet created — use them as you add API endpoints.
- BDD: `docs/features/` folder exists; add Gherkin scenarios here (e.g., `vacancies-market/managing_vacancies.feature`) as you implement features.
- Existing unit tests: `tests/Unit/VacancyTest.php`, `tests/Unit/InterviewerTest.php`, `tests/Unit/ExampleTest.php` — use as reference for test style (unit tests extend `PHPUnit\Framework\TestCase` directly, not Laravel's).
- Feature tests: `tests/Feature/` is currently empty; use for acceptance/integration tests.

Copilot / agent instructions
- **Start here:** `./.github/copilot-instructions.md` (short PSR-12, Laravel 13, PHP 8.5 rules).
- This file (`AGENTS.md`) extends those rules with codebase-specific patterns and commands.

CI and contract publication
- CI validates documentation and contracts (see `docs/README.md`). Update OpenAPI/AsyncAPI and BDD scenarios together with code changes.

Assumptions & constraints
- Documentation in `docs/` is the primary source of architectural truth; code follows the docs.
- Event/contract changes require cross‑service coordination (outbox / idempotency rules).

---
