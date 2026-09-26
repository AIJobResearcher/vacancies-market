# Agent Instructions

## 1. General

- **Stack:** PHP 8.5, Laravel 13, PostgreSQL 16, Redis.
- **Approaches:** Clean Architecture (DDD, event-driven, CQRS); GRASP, SOLID,
  YAGNI, KISS.

## 2. Documentation

- Docs are not gospel; report contradictions to the user.
- Key files (reference and Ubiquitous Language):
  `docs/domain/bounded-contexts/vacancies-market.md`,
  `docs/api/vacancies-market/openapi.yaml`,
  `docs/event-storming/vacancy-market.md`, `docs/context-map.md`.
- If an answer exists there, provide a reference, not a summary.
- Before implementing or changing an endpoint, read its OpenAPI section and
  compare it with the current code; on divergence report it in one line and
  ask which side is authoritative before writing code.

## 3. Technical Requirements

- Use current stack features (see 1), avoid outdated approaches; code must
  pass strict static analysis (phpcs PSR-12+Slevomat, phpstan 10, psalm 1).
- Dependency direction: Presentation → Application → Domain; Domain must not
  depend on Infrastructure. Business logic in Domain, orchestration in
  Application.
- Schema changes only via migrations.
- Use framework security defaults; validate at boundary; centralize error
  handling and logging.
- Local runtime: Docker Compose service `vacancies-market-app` (host port
  8001 → container 8000, bind mount `.:/var/www`), database
  `vacancies-market-postgres`; check behaviour through `docker exec` with
  `curl` and `psql`, never a host PHP process.
- After creating or overwriting files run
  `chmod -R go+rX app routes bootstrap database public`: agent-written files
  are mode 600 while php-fpm runs as `www-data`, otherwise the class loader
  fails with "Permission denied".

## 4. Code Quality

- Before editing or writing any file, load and strictly obey its standard:
  - Laravel code → `.ai-agent/standards/laravel-standards.md`
  - PHP code → `.ai-agent/standards/php-standards.md`
  - Text in `*.md` files → `.ai-agent/standards/md-files-standards.md`

## 5. Token Efficiency

- Ambiguous scope or design decision: ask at most one round, with options and
  a recommended default. Before changing code, state goal, affected
  files/layers, and chosen approach in at most five short bullets.
- Read `.ai-agent/DECISIONS.md` before designing anything and apply its
  entries without discussion; if a task contradicts an entry, report the
  contradiction in one line and ask.
- Deliberation budget: never restate the task, the standards, or code just
  read; never re-open a decision settled in this session or in
  `.ai-agent/DECISIONS.md`.
- Read and edit: grep first, then read only the needed range; group all edits
  of one file into one step. If a write or edit is rejected because the file
  changed (parallel IDE edits), re-read that file once and retry; never
  rescan the repository.
- Web search: only when the answer is absent from code, docs, and the
  framework source; prefer one search with up to four queries over repeated
  single searches; cap `web_fetch` to pages you will actually use and save
  conclusions, never raw page text, under `.ai-agent/artifacts/`.
- Reply in a single short line: `Done — <files>` or `Done — <file>: <2-3
  words>`. Return diff hunks only. No explanations, rationale, summaries, or
  restating; nothing else unless asked.
- Do not generate tests, migrations, factories, docs, or extra code without
  explicit request. When tests are requested, follow patterns in `tests/`.
- Save AI temp artifacts (plans, task lists, test/research results) under
  `.ai-agent/artifacts/`.
- Cap command and log output shown to the model: pipe to `head`/`tail`/`grep`
  and show only relevant lines or the error tail, never the full dump.
- Reference a file by path and line range, not content, unless requested.
  Keep saved artifacts under 40 lines unless more is required.
- Reuse known values and avoid repeating identical operations in a session.
- Start a new session per feature: at about 20 messages or before a new
  feature, write a handoff note (state, files, next step, open questions) to
  `.ai-agent/artifacts/` and continue in a fresh session.

## 5a. Definition of done

- A task is done only when its requested scope is fully addressed — no
  partial hand-offs expecting a follow-up. Ask on genuine blockers; otherwise
  complete it and report per Token Efficiency (see 5).

## 6. Limitations

- Only the Vacancies Market service. Do not change API, architecture,
  existing files, configs (`phpcs.xml.dist`, `phpstan.neon`, `psalm.xml`,
  `composer.json`, etc.), dependencies, or documentation without explicit
  request.
- Never run analyzers/tests/migrations/dependency updates on your own
  initiative (phpcs, phpstan, psalm, deptrac, phpunit, composer). Run them
  ONLY on an explicit "run" request or "fix and verify"; a pasted error list
  alone means fix exactly what is reported and STOP — no tool runs, no extra
  analyzers, no widened scope. Do not self-verify edits by running gates;
  self-check with `php -l` and a runtime call (see 3) instead. On an explicit
  run request, scope to the changed files only and re-report briefly.
