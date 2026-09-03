# Project Rules: Vacancies Market Service

> Apply to any request related to this project. If the question is outside these rules, clarify first.

---

## 1. General Information

- **Service:** `vacancies-market` (AIJobResearcher).
- **Stack:** PHP 8.5, Laravel 13, PostgreSQL 16, Redis.
- **Architecture:** Clean Architecture (Presentation → Application → Domain → Infrastructure).
- **Approaches:** DDD, Event-Driven, CQRS, GRASP, SOLID, YAGNI, KISS, Superpowers (brainstorming → plan → TDD → sub-agents → review).
- **Documentation:** located in `docs/`. Refer to it as needed. Architectural decisions are in `docs/adr/`.

---

## 2. Ubiquitous Language

Source of truth: `docs/bounded-contexts/vacancies-market.md` (section "Aggregates and entities"). Use it for names and relationships. **Apply Ubiquitous Language in class, method, and variable names.**

---

## 3. Technical Requirements

- Use current features of PHP 8.5, Laravel 13, PostgreSQL 16; avoid outdated approaches.
- Follow PSR standards.
- Almost never add comments; if truly necessary, English only.
- Strict dependency direction per Clean Architecture: Presentation → Application → Domain; Domain does not depend on Infrastructure. Business logic in Domain, orchestration in Application.
- Prefer mature enterprise patterns and practices over random GitHub examples.
- For Event-Driven operations, use Outbox Pattern and ensure idempotency.
- Schema changes only via migrations.
- Handle errors centrally and log with context.
- Security: validation, protection against SQL injection, XSS, CSRF, secure secret storage.
- Before completing changes, run `vendor/bin/pint` and `composer test`.
- When changing events or API, update contracts in `docs/asyncapi/events.yaml` or `docs/api/<service>/` accordingly.

---

## 4. Token Efficiency

- Ask up to 3–5 clarifying questions when ambiguous.
- If an answer exists in `docs/`, provide a reference, not a summary.
- Propose the simplest solution first; alternatives on request.
- Show only diffs during refactoring.
- Do not generate tests, examples, or extra code without explicit request.
- Save AI temp artifacts (plans, task lists, test/research results, etc.) under `.dsh/docs/`.

---

## 5. Limitations

- Only the Vacancies Market service. Do not change API, architecture, existing files, or dependencies without explicit request.