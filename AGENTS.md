## 1. General
- **Stack:** PHP 8.5, Laravel 13, PostgreSQL 16, Redis.
- **Approaches:** Clean Architecture (DDD, event-driven, CQRS); GRASP, SOLID, YAGNI, KISS.

## 2. Documentation
- Docs are not gospel; report contradictions to the user.
- Key files (reference and Ubiquitous Language): `docs/domain/bounded-contexts/vacancies-market.md`, `docs/api/vacancies-market/openapi.yaml`, `docs/event-storming/vacancy-market.md`, `docs/context-map.md`.
- If an answer exists there, provide a reference, not a summary.

## 3. Technical Requirements
- Use current stack features (see 1), avoid outdated approaches; code must pass strict static analysis (phpcs PSR-12+Slevomat, phpstan 10, psalm 1).
- Dependency direction: Presentation → Application → Domain; Domain must not depend on Infrastructure. Business logic in Domain, orchestration in Application.
- Schema changes only via migrations.
- Use framework security defaults; validate at boundary; centralize error handling and logging.

## 4. Code Quality
- Before editing or writing any file, load and strictly obey its standard:
   - Laravel code → `.ai-agent/standards/laravel-standards.md`
   - PHP code → `.ai-agent/standards/php-standarts.md`
   - Text in `*.md` files → `.ai-agent/standards/md-files-standards.md`

## 5. Token Efficiency
- Ambiguous scope or design decision: ask at most one round. Before changing code, restate the task in one line and name the affected files/layers.
- Reply in a single short line: "Done — <files>" or "Done — <file>: <2-3 words>". Return diff hunks only. No explanations, rationale, summaries, or restating; nothing else unless asked.
- Do not generate tests, migrations, factories, docs, or extra code without explicit request. When tests are requested, follow patterns in `tests/`.
- Save AI temp artifacts (plans, task lists, test/research results) under `.ai-agent/artifacts/`.
- Never read a whole file or log: grep first, then read only the matching lines/range; reuse already-known content instead of re-reading a summarized file.
- Cap command and log output shown to the model: pipe to `head`/`tail`/`grep` and show only relevant lines or the error tail, never the full dump.
- Reference a file by path and line range, not content, unless requested. Keep saved artifacts (plans, lists, summaries) under 40 lines unless more is required.
- Reuse known values and avoid repeating identical operations within a session.
- If conversation exceeds ~20 messages or context grows large, suggest summarization or a new session.

## 5a. Definition of done
- A task is done only when its requested scope is fully addressed — no partial hand-offs expecting a follow-up. Ask on genuine blockers; otherwise complete it and report per Token Efficiency (see 5).

## 6. Limitations
- Only the Vacancies Market service. Do not change API, architecture, existing files, configs (`phpcs.xml.dist`, `phpstan.neon`, `psalm.xml`, `composer.json`, etc.), dependencies, or documentation without explicit request.
- Never run analyzers/tests/migrations/dependency updates on your own initiative (phpcs, phpstan, psalm, deptrac, phpunit, composer). Run them ONLY on an explicit "run" request or "fix and verify"; a pasted error list alone means fix exactly what is reported and STOP — no tool runs, no extra analyzers, no widened scope. Do not self-verify edits by running gates. On an explicit run request, scope to the changed files only and re-report briefly.
