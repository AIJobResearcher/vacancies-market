# Copilot / Agent instructions for this repository

Short rules for automated suggestions (Copilot, IDE assistants, small agents):

1. Code style
- Follow PSR‑12 / Laravel 13 conventions for PHP. Use `vendor/bin/pint` for formatting if available.
- Types: PHP 8.5 — prefer union types, readonly properties, and strict typing where possible.

2. Tests and CI
- All business‑logic changes require unit tests (in `tests/Unit/`) and, when appropriate, acceptance/integration tests (in `tests/Feature/`).
- Before opening a PR run:
```bash
composer test
vendor/bin/pint
```
- Small documentation-only changes do not require tests, but contract changes (OpenAPI/AsyncAPI) must pass CI validations.

3. PR policy (for generated code)
- Keep PRs minimal and include a description: what changed, why, and how to test.
- For breaking changes (especially events) do the following:
  - bump `event_version` in `docs/asyncapi/events.yaml`,
  - create/update an ADR in `docs/adr/`,
  - add integration tests or a migration plan.

4. Working with contracts (OpenAPI / AsyncAPI)
- Source of truth for events: `docs/asyncapi/events.yaml`.
- If an agent generates code from a schema — update the schema first, then code and tests.

5. Security and secrets
- Never commit real secrets to the repository. Use `.env` for local development and CI secrets for pipelines.

6. Small helpers
- Local build helper: `make up` → `make exec` → run `composer test` inside the container.
- Quick code search: grep across `app/Domain/` and `docs/` when looking for domain objects.

7. When unsure
- If a task affects cross‑service contracts (events, API), open a draft PR with minimal changes and request an architecture review from the author/architect.

8. Code comments
- Document nontrivial logic (idempotency, outbox, deadlines) with comments and reference the relevant ADR in `docs/adr/`.

---

These instructions are intentionally short — extend them via `AGENTS.md` as needed.