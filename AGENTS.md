## 1. General
- **Stack:** PHP 8.5, Laravel 13, PostgreSQL 16, Redis.
- **Approaches:** Clean Architecture (DDD, event-driven, CQRS); GRASP, SOLID, YAGNI, KISS.

## 2. Documentation

- Docs are not gospel; report contradictions to the user.
- Key files (use for reference and Ubiquitous Language naming): `docs/domain/bounded-contexts/vacancies-market.md`, `docs/api/vacancies-market/openapi.yaml`, `docs/event-storming/vacancy-market.md`, `docs/context-map.md`.
- If an answer exists there, provide a reference, not a summary.

## 3. Technical Requirements

- Use current stack features (see General) and avoid outdated approaches; code must pass strict static analysis (phpcs PSR-12+Slevomat, phpstan 10, psalm 1).
- Dependency direction: Presentation → Application → Domain; Domain not depend on Infrastructure. Business logic in Domain, orchestration in Application.
- Schema changes only via migrations.
- Use framework security defaults; validate at boundary; centralize error handling and logging.

## 4. Code Quality

Write each artifact per its matching standard (read it first, comply strictly):

- Laravel code → `.ai-agent/standards/laravel-standards.md`
- PHP code → `.ai-agent/standards/php-standarts.md`
- Text in `*.md` files → `.ai-agent/standards/md-files-standards.md`

## 5. Token Efficiency

- If prompt lacks concrete scope or design decision is ambiguous, ask at most one round of questions.
- Reply in a single short line: "Done — <files>" or "Done — <file>: <2-3 words>". Return diff hunks only. No explanations, rationale, summaries of steps, or restating; nothing else unless asked.
- Do not generate tests, migrations, factories, docs, or extra code without explicit request. When tests are requested, follow existing patterns in `tests/`.
- Never "confirm" a change by running analyzers or tests unless asked — an unrequested tool run is a violation, not best practice.
- Save AI temp artifacts (plans, task lists, test/research results) under `.dsh/docs/`.
- Before changing code, restate the task in one line and name the affected files/layers — confirm scope when a decision is ambiguous.
- Never read a whole file or log. Grep first, then read only the matching lines/range. Whole-file and whole-log reads are the top context/token waste; reading a file you already summarized is forbidden — reuse the known content instead.
- Cap command and log output shown to the model: pipe to `head`/`tail`/`grep` and show only the relevant lines or the error tail (e.g. last lines of a phpcs/phpunit/CI log), never the full dump.
- Reuse known values and avoid repeating identical operations within a session.
- If conversation exceeds ~20 messages or context becomes large, suggest summarization or starting a new session.
- Keep saved artifacts (plans, lists, summaries) under 40 lines unless more is required.
- When referencing a file, provide a path and line range, not the file content, unless explicitly requested.

## 5a. Definition of done

- A task is done only when its requested scope is fully addressed — no partial
  hand-offs expecting a follow-up. Ask on genuine blockers; otherwise complete
  it and report per Token Efficiency.

## 6. Limitations

- Only the Vacancies Market service. Do not change API, architecture, existing files, configs (`phpcs.xml.dist`, `phpstan.neon`, `psalm.xml`, `composer.json`, etc.), dependencies, or documentation without explicit request.
- Never run analyzers/tests/migrations/dependency updates on your own
  initiative (phpcs, phpstan, psalm, deptrac, phpunit, composer). Run them ONLY
  on an explicit "run" request or "fix and verify"; a pasted error list alone
  means fix exactly what is reported and STOP — no tool runs, no extra
  analyzers, no widened scope. Do not self-verify edits by running gates. On an
  explicit run request, scope to the changed files only and re-report briefly.