---
title: "AGENTS Quick Guide"
summary: "Machine-friendly quick guide for AI agents: reading priority, commands, patterns, and rules for working with contracts and events."
tags: ["agents","quickstart","events","contracts"]
owners: ["subscribe.software.engineer@gmail.com"]
last_updated: "2026-06-18"
version: "1.0"
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
  - Presentation (HTTP): `app/Http/Controllers/`
  - Application (use cases / handlers / DTOs): `app/Application/`
  - Domain (entities, value objects, events, repository interfaces): `app/Domain/`
  - Infrastructure (implementations, persistence, messaging, ACL): `app/Infrastructure/`
- Outbox pattern: used for reliable event publication (see ADR in `docs/adr/`).
- Idempotency: deduplication via `processed_events` table — consumers check `event_id`.
- Search: OpenSearch indexing is done asynchronously via RabbitMQ events.

Rules for working with events (concrete)
- When adding or changing an event: update `docs/asyncapi/events.yaml` and bump `event_version` for breaking changes.
- Add or update an ADR if the change has architectural impact (`docs/adr/`).
- Update the producer in `app/Infrastructure/Messaging/` and/or the handler in `app/Application/Handlers/`.
- Add tests: unit tests for business logic and integration/consumer tests for downstream processing.

Typical tasks — what to change and where
- Add a new handler for `ReplyCreated` event:
  1) update `docs/asyncapi/events.yaml` (event schema),
  2) implement `app/Application/Handlers/ReplyCreatedHandler.php`,
  3) if needed, add persistence implementation in `app/Infrastructure/Persistence/`,
  4) add unit tests in `tests/Unit/` and integration/consumer tests in `tests/Feature/`.
- Add a new API endpoint:
  1) add route in `routes/web.php`,
  2) create controller in `app/Http/Controllers/`,
  3) place use case in `app/Application/`,
  4) update `docs/api/<service>/openapi.yaml` and ensure CI passes.

Quick safety and quality tips
- Always run `composer test` and `vendor/bin/pint` (if available) before opening a PR.
- Keep PRs small and focused (one change — one goal). Tests are mandatory for business logic changes.
- For breaking event changes: bump `event_version`, add an ADR and notify downstream teams via PR/CHANGELOG.

Agent prompt templates (copy & adapt)
- "Add event VacancyClosed: update `docs/asyncapi/events.yaml`, create handler `app/Application/Handlers/VacancyClosedHandler.php`, add unit test `tests/Unit/VacancyClosedHandlerTest.php`."
- "Refactor method in `app/Domain/Entities/Vacancy.php` preserving backward compatibility; add tests for edge cases and invariants."

Source files and quick mapping
- Docs: `docs/` (index: `docs/README.md`, TOC: `docs/TOC.md`).
- Contracts: `docs/asyncapi/events.yaml`, `docs/api/`.
- BDD: `docs/features/` (Gherkin scenarios).

Copilot / agent instructions
- Additional short instructions for Copilot/IDE agents are in `./.github/copilot-instructions.md`.

CI and contract publication
- CI validates documentation and contracts (see `docs/README.md`). Update OpenAPI/AsyncAPI and BDD scenarios together with code changes.

Assumptions & constraints
- Documentation in `docs/` is the primary source of architectural truth; code follows the docs.
- Event/contract changes require cross‑service coordination (outbox / idempotency rules).

---

If you want, I can also generate a PR with these files and an additional `docs/TOC.md` or `docs/INDEX.json`.
