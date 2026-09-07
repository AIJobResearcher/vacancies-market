# Laravel Code Standards

Rules specific to Laravel code (Eloquent, framework integration, layering).
Follow these when writing or editing Laravel code.

## Layering

- Keep Domain free of Eloquent models, DTOs, and infrastructure details.
  Dependency direction: Presentation → Application → Domain; Domain does not
  depend on Infrastructure.

## Eloquent models

- Add `@property` / `@property-read` for all magic properties.
- Type-hint relations with `@return` and `@param` where needed.
- Do not let Eloquent models leak into Domain; repositories return domain
  aggregates, not query builders/models.

## Relation existence checks (JOIN + EXISTS)

In infrastructure/repository queries, test related-record existence with an
explicit `JOIN` + `->exists()` filtered by parent attributes:

- Prefer two separate `exists()` queries, returning `true` on the first match; a
  single `UNION ALL ... ->exists()` is allowed to cut round-trips.
- Avoid `LEFT/RIGHT JOIN` + `IS NOT NULL`, `whereHas` (subqueries), or loading
  models then checking relations — less efficient on large data.
