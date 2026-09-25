# Settled decisions

Status: living · Updated: 2026-09-25 · Owner: engineering

Decisions the agent applies as defaults and never re-opens. Each entry is
decision — reason — date. A task that contradicts an entry is reported in
one line and confirmed with the user before any code changes.

Generic conventions (layering, HTTP layer, validation, OpenAPI contracts,
temporary code, file permissions, decision-file format) moved into proposals
for the shared standards — see `.ai-agent/user.data/standards-improuvments.md`
section 6; keep applying them until the standards land.

## 1. Domain boundaries

- **1.1** `Employer`, `Job`, `Interviewer` are separate root aggregates; the
  `Vacancy` aggregate nests only their ids, never their objects — 2026-09-21.
- **1.2** When a read response needs data owned by another aggregate, add a
  repository method; never traverse foreign relations from an aggregate —
  2026-09-21.

## 2. Read side of the repository

- **2.1** Read-model methods live in the repository port; `findById` returns
  the aggregate and is reused inside read methods — 2026-09-21.
- **2.2** A repository may return Eloquent models and model collections to
  Application and Presentation; clause 1.2 of `laravel-standards.md`
  ("repositories never return models or query builders") is deliberately
  ignored in this project — 2026-09-24.

## 3. Local environment

- **3.1** Runtime is the Docker Compose service `vacancies-market-app` (host
  port 8001 → container 8000, bind mount `.:/var/www`), database
  `vacancies-market-postgres`; verification goes through `docker exec` with
  `curl` and `psql` — 2026-09-15.

## 4. Service providers

- **4.1** Every service provider implements
  `Illuminate\Contracts\Support\DeferrableProvider` and lists its bindings in
  `provides()`, so the container loads it on first use — 2026-09-24.
- **4.2** `register()` stays side-effect free and an empty `boot()` is
  removed: a deferred provider is not loaded, and therefore not booted, until
  one of its services is requested — 2026-09-24.
- **4.3** Binding lists live in one private constant per provider, reused by
  both `register()` and `provides()`, so the two can never drift apart —
  2026-09-24.
- **4.4** Infrastructure bindings are split by concern (`MapperServiceProvider`,
  `RepositoryServiceProvider`); a provider left without bindings is deleted
  rather than kept empty — 2026-09-24.

## 5. Documentation, Domain and database constraints

- **5.1** Documented requirements are implemented in the Domain; the database
  never becomes their source of truth — 2026-09-25.
- **5.2** A migration must not contradict the documentation: when a database
  feature (cascade delete, unique, check) would break a documented rule, the
  documented rule wins and that feature is not used — 2026-09-25.
- **5.3** Database constraints are a secondary tool — desirable, not
  mandatory. Use them where they match the documented rules without changing
  them (`unique`, foreign keys), and choose per situation; judge each case on
  its own instead of applying a constraint by default — 2026-09-25.
- **5.4** A database constraint stricter than the documentation is a
  deliberate, recorded choice, not a default; state the divergence where the
  constraint is declared — 2026-09-25.
- **5.5** Case on record: the foreign keys from `job_requirements` and
  `vacancy_requirement_assignments` to `requirements` use
  `ON DELETE RESTRICT`, not `cascade`, because
  `docs/domain/bounded-contexts/vacancies-market.md` 6.7.3 forbids deleting a
  referenced Requirement — 2026-09-25.
