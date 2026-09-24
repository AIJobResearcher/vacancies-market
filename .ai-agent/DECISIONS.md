# Settled decisions

Status: living · Updated: 2026-09-21 · Owner: engineering

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

## 3. Local environment

- **3.1** Runtime is the Docker Compose service `vacancies-market-app` (host
  port 8001 → container 8000, bind mount `.:/var/www`), database
  `vacancies-market-postgres`; verification goes through `docker exec` with
  `curl` and `psql` — 2026-09-15.
