# Settled decisions

Status: living · Updated: 2026-09-25 · Owner: engineering

Defaults the agent applies without re-opening; entry form is decision —
reason — date. A task that contradicts an entry is reported in one line and
confirmed with the user before code changes. Only project-local rules live
here: the generic conventions (layering, HTTP layer, validation, OpenAPI
contracts, temporary code, file permissions, this file's format) are proposed
for the shared standards in `.ai-agent/user.data/standards-improuvments.md`
section 6 and stay in force until those land.

## 1. Domain boundaries

- **1.1** `Employer`, `Job`, `Interviewer` are separate root aggregates; the
  `Vacancy` aggregate nests only their ids, never their objects — 2026-09-21.
- **1.2** A read response needing data owned by another aggregate gets a new
  repository method; an aggregate never traverses foreign relations
  — 2026-09-21.

## 2. Read side of the repository

- **2.1** Read-model methods live in the repository port; `findById` returns
  the aggregate and is reused inside them — 2026-09-21.
- **2.2** Eloquent models stay an Infrastructure detail even though a
  repository method may return them internally: clause 1.2 of
  `laravel-standards.md` ("repositories never return models or query
  builders") is deliberately ignored here, but no port or outer-layer
  signature may name a model — deptrac allows Domain only `External`
  (`skip_violations` is empty) and canonical DDD keeps the ORM out of the web
  layer — so a port exposes arrays or Domain DTOs (9.3) — 2026-09-24.

## 3. Local environment

- **3.1** Runtime is the Docker Compose service `vacancies-market-app`
  (host port 8001 → container 8000, bind mount `.:/var/www`), database
  `vacancies-market-postgres`; verify through `docker exec` with `curl` and
  `psql`, never a host PHP process — 2026-09-15.

## 4. Service providers

- **4.1** Every provider implements `DeferrableProvider` and lists its
  bindings in `provides()`, so the container loads it on first use; those
  bindings live in one private constant reused by `register()` and
  `provides()`, so the two cannot drift apart — 2026-09-24.
- **4.2** `register()` stays side-effect free and an empty `boot()` is
  removed: a deferred provider is not booted until one of its services is
  requested — 2026-09-24.
- **4.3** Infrastructure bindings are split by concern
  (`MapperServiceProvider`, `RepositoryServiceProvider`); a provider left
  without bindings is deleted, not kept empty — 2026-09-24.

## 5. Documentation, Domain and database constraints

- **5.1** Documented requirements are implemented in the Domain; the database
  never becomes their source of truth — 2026-09-25.
- **5.2** A migration never contradicts the documentation: where a database
  feature (cascade delete, unique, check) would break a documented rule, the
  rule wins and that feature is not used — 2026-09-25.
- **5.3** Database constraints are desirable, not mandatory: use them where
  they match the documented rules without changing them (`unique`, foreign
  keys) and judge each case on its own instead of applying one by default; a
  constraint stricter than the documentation is a deliberate choice stated
  where the constraint is declared — 2026-09-25.
- **5.4** On record: the foreign keys from `job_requirements` and
  `vacancy_requirement_assignments` to `requirements` use
  `ON DELETE RESTRICT`, not `cascade`, because
  `docs/domain/bounded-contexts/vacancies-market.md` 6.7.3 forbids deleting
  a referenced Requirement — 2026-09-25.

## 6. Migrations

- **6.1** Migrations run only on an explicit request — `migrate`,
  `migrate:fresh`, `migrate:rollback` and `migrate --pretend` included —
  because they change shared state and can destroy local data — 2026-09-25.
- **6.2** Applying a schema change, and the `migrate:fresh` it needs when the
  migration was already applied, is confirmed with the user before the run
  — 2026-09-25.

## 7. Validation and static typing

- **7.1** A value validated at the boundary is not validated again deeper in
  the layers: the `FormRequest` rules are its single guarantee, and repeated
  checks are dead code — 2026-09-25.
- **7.2** Where an analyzer cannot infer the type of already validated data
  (`ValidatedInput::array()` is `array<mixed>`), state it in an inline
  comment (`/** @var list<string> $jobIds */`), never with a runtime check,
  a cast or an ignore. The comment is a claim: keep it next to the rule that
  backs it, so weakening that rule updates both. A cast that truly converts
  is untouched — an `integer`-ruled field can arrive as a numeric string
  (`int<0, max>|null|numeric-string`), so
  `is_numeric($value) ? (int) $value : null` stays — 2026-09-25.
- **7.3** A wrong or vague type is fixed at its origin, not worked around:
  correct the declaration closest to where the type is produced — a `@param`
  on the method that builds the value beats a local `@var` on the line that
  consumes it — instead of adding a second comment or code that serves only
  the analyzer — 2026-09-25.
- **7.4** Where the two analyzers disagree about a field, use the type both
  accept. A validated field read is the known case: psalm types
  `ValidatedInput::input('country')` from `rules()` and calls the matching
  `@var` `UnnecessaryVarAnnotation` (`@var`, `@psalm-var` and `@phpstan-var`
  merge into one tag, so no variant escapes this), while phpstan, without the
  larastan extension, sees `mixed`. The answer is a boundary narrowing helper
  that takes the value and not the key —
  `nullableString(mixed $value): ?string` called as
  `$this->nullableString($input->input('country'))`: the native `mixed` hint
  is invisible to phpcs (`DisallowMixedTypeHint` reads doc comments only),
  psalm sees no annotation, phpstan gets its `string|null` — 2026-09-25.
- **7.5** When a `list` is fed into `LengthAwarePaginator`, no single
  annotation satisfies both analyzers: psalm refines the key to
  `int<0, max>`, phpstan keeps `int`, and the invariant `TKey` makes the two
  mutually exclusive. Claim the neutral `array<int, ...>` at the boundary —
  the port's `@return` and the page DTO's `$items` — and
  `LengthAwarePaginator<int, ...>` on the use case: less precise than
  `list<...>`, still true, accepted by both. The implementation keeps its own
  narrower `list<...>` (legal covariance), and the neutral key must sit on the
  type the use case reads, otherwise psalm infers `int<0, max>` from a `list`
  and reports `InvalidReturnType` — 2026-09-25.
- **7.6** Boundary accessors are read with literal keys: the laravel plugin
  types `ValidatedInput::input('field')` (and `validated()`, `string()`,
  `integer()`, `enum()`, `date()`) from the request's `rules()` through the
  generic `TRequest`, but a variable key defeats that lookup and hands back
  `mixed` (`MixedAssignment`) — so a helper taking `string $key` is wrong by
  construction. Helpers take no `ValidatedInput` either and call
  `$this->safe()` themselves: psalm reads the plugin stubs
  (`ValidatedInput<static>` is invariant) while phpstan sees a non-generic
  class (`generics.notGeneric` for any annotation), so the generic must never
  cross a signature — 2026-09-25.
- **7.7** `mixed` is a last resort, not a default: when the keys and value
  types are known from the code that produces the value (the `select()`
  column list, the model's `@property`/`casts`, the resource that reads those
  keys), write that concrete shape instead of `array<string, mixed>`, taken
  verbatim from the consumer that already declares it, so a wrong key fails
  at the contract rather than at runtime. phpcs enforces this
  (`SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint` fails on `mixed`
  even inside a docblock). One exception survives: an opaque payload whose
  shape another part of the system owns — the outbox `payload`, one column
  holding the JSON of six domain events, written and never read here — keeps
  `array<string, mixed>` under an explicit `@phpcsSuppress` on the class,
  because a union of today's event fields is false precision that every new
  event invalidates — 2026-09-25.
- **7.8** When the real value carries keys beyond the declared ones, declare
  an unsealed shape with a trailing `...` (`array{total: int, ...}`, which
  both analyzers support) instead of a sealed one that denies the extra keys.
  This also types a framework payload whose tail cannot be written without
  `mixed` (the paginator array handed to `paginationInformation()`): claim
  only the keys the code relies on — a bare `array` fails
  `missingType.iterableValue` even for an unused parameter, and the full shape
  would need the `mixed` that 7.7 bans. Extra keys are the parameter's
  problem, not the caller's: when psalm reports `InvalidArgument` or phpstan
  `argument.type` because a sealed shape receives a wider array, unseal the
  parameter — never loosen the caller's shape, suppress, or build a trimmed
  copy — 2026-09-25.
- **7.9** A type lost by the way we call, not by a missing declaration, is
  restored by changing the call, not by an `@var`: when a fluent chain goes
  through `@mixin`/`__call` forwarding, the generic is dropped at the first
  forwarded method (`Builder<VacancyModel>->join()` gives `Query\Builder`, and
  `paginate()`/`first()`/`get()` then return `mixed`), so call the typed
  method on the receiver whose class binds the template — the Eloquent
  builder, `@use BuildsQueries<TModel>` — and issue the forwarded methods
  (`join()`, `select()`, `orderByDesc()`) as separate mutating statements.
  A relation is the same case (7.11).
  The analyzer reports it as `argument.type` on a callback or on a mapper
  call that receives `stdClass` instead of the model
  (`Model::query()->leftJoin(...)->get()` is a `Query\Builder` chain, so its
  rows are `stdClass`), `return.unusedType` on the declared type, or
  `return.type` when the value read off `mixed` degrades to a bare
  `list`/`array`. An `@var` is left only for what a bare producer declaration
  cannot express (`Model::toArray()` is `array`) — 2026-09-25.
- **7.10** A type established at the boundary does not travel with the call,
  so the consumer restates it as its own contract — `@param list<string>
  $jobIds` on the use case — in the wording of the boundary accessor that
  produces it; one such `@param` clears both `missingType.iterableValue` and
  the `argument.type` on the call. Likewise a payload shape crossing layers
  is declared identically on the Domain interface, its Infrastructure
  implementation, the Application use case and the Presentation resource, and
  changes to it update every declaration at once — a narrower `@return` in an
  implementation is legal covariance and never flags the mismatch, and a use
  case that guards `|null` away declares the shape without `null`
  — 2026-09-25.
- **7.11** Write a relation that carries ordering or extra constraints as a
  variable that is mutated and then returned, never as a chained `return`:

  ```php
  $relation = $this->belongsToMany(
      RequirementModel::class,
      'vacancy_requirement_assignments',
      'vacancy_id',
      'requirement_id',
  );

  $relation->orderBy('requirements.title');

  return $relation;
  ```

  `Relation` is `@mixin Builder<TRelatedModel>` (`Relation.php:22`), so every
  forwarded method used in a chain (`orderBy()`, `whereNull()`, `where()`,
  `limit()`) replaces the relation type with `Builder`, and phpstan answers
  `return.type`: "should return `BelongsToMany<…>` but returns
  `Illuminate\Database\Query\Builder`". The variable keeps the declared
  `BelongsToMany`/`HasOne`/`HasMany`/`BelongsTo` intact, and the mutation
  still lands on the same query. A declared relation method is the better
  choice where one exists — `wherePivotNull()`, `orderByPivot()`,
  `orderByPivotDesc()` are `@return $this` and may stay in the chain.
- **7.12** The `@return` of a relation spells out every template parameter the
  class declares: `BelongsToMany` takes four
  (`BelongsToMany<RequirementModel,$this,Pivot,'pivot'>` — psalm reports
  `MissingTemplateParam` for the two-parameter form), while `HasOne`,
  `HasMany` and `BelongsTo` take two (`HasOne<Model,$this>`). The pivot pair
  stays `Pivot`/`'pivot'` unless `using()` names a custom pivot model
  — 2026-09-25.

## 8. Comments and suppressions

- **8.1** A suppression is the last resort, never the first fix: after an
  analyzer error, look for a construct both analyzers accept before reaching
  for a comment (7.4, 7.6, 7.7); only a case neither analyzer can satisfy by
  code stays suppressed (8.3, 8.4, 8.5), and the reason is what the
  suppression text states — 2026-09-25.
- **8.2** Suppressions and comments are re-checked with the code they
  describe: a `@psalm-suppress`/`@phpcsSuppress` whose condition no longer
  holds — most often because the symbol is now used — and a docblock, `@var`
  or inline note that stopped matching are deleted or corrected in the same
  change, never left for a later pass. Neither psalm
  (`findUnusedPsalmSuppress` is off here) nor phpcs reports a stale one, and
  an obsolete suppression silently hides the next real error — 2026-09-25.
- **8.3** Container-resolved collaborators are the canonical
  `PossiblyUnusedMethod` case: a constructor the Laravel container
  instantiates has no visible `new` — use cases, and every Infrastructure
  implementation bound to a Domain interface in `RepositoryServiceProvider`
  (`EmployerEloquentRepository` is built from
  `EmployerRepositoryInterface::class`) — so it carries a bare
  `/** @psalm-suppress PossiblyUnusedMethod */` directly above it, one per
  constructor rather than at class level, for all such classes in the same
  change — 2026-09-25.
- **8.4** Framework hooks are suppressed on the method, not at class level: a
  method the framework invokes dynamically (`paginationInformation()`, found
  with `method_exists` in `PaginatedResourceResponse`) looks unused and its
  parameter list is dictated by that call, so it carries
  `@psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam` in its docblock
  next to the `@phpcsSuppress` for the same parameters — 2026-09-25.
- **8.5** Route-referenced classes are taken as used: an invokable controller
  is named only in `routes/api.php`, which psalm does not analyse, so the
  class carries `/** @psalm-suppress UnusedClass */` above the declaration —
  class level, because the whole class is what looks unused (the middleware
  and event classes already do) — 2026-09-25.
- **8.6** A pasted analyzer report is checked against the current file before
  acting: when the message quotes a declaration the file no longer has (a
  shape already unsealed, an `@var` already rewritten), the report is stale —
  the fix is in place, and the answer is "already fixed" plus a re-run, never
  a second edit — 2026-09-25.
- **8.7** `@psalm-suppress` takes a comma-separated list: psalm parses the
  first issue name, further names after commas, and anything after a space as
  a free-text description — so `@psalm-suppress A B` silences only `A` and
  the rest is decoration (three such lines lived in this codebase). Write
  `A, B, C` — 2026-09-25.

## 9. Performance and data access

- **9.1** No redundant work in the request path: every pass, copy or
  conversion over a result set must earn its place, because the service runs
  under load. A plain `foreach` beats the collection method that wraps it:
  `array_values()` after `paginate()` is a no-op copy, `map(...)->all()` and
  `Collection::toArray()` allocate an intermediate collection and a closure
  call per item, while the loop writes straight into the result array (0.05
  against 0.08 µs per item, measured) — so build a result array with a loop
  wherever the loop stays as readable, and keep the row shape on the row
  local (7.2), leaving the accumulator inferred as a `list` of that shape.
  The ban on code that serves only the analyzer (7.3) extends to code that
  serves nothing — 2026-09-25.
- **9.2** Select only the columns the response needs; a value taken from a join
  has no model property, so it is selected under its own alias
  (`employers.title as employer_title`) and declared on the model
  (`@property-read string $employer_title`, marked select-dependent) — the
  read stays one round-trip without re-loading the row as a relation, and the
  value is typed for the mapper — 2026-09-25.
- **9.3** A read model crosses the port as a Domain DTO built by the
  Infrastructure mapper from the loaded models: `VacancyPreviewPageDto`
  (`list<VacancyPreviewDto>` + `total`) for a paginated list read,
  `VacancyDetailDto` (with `EmployerSummaryDto` / `InterviewerSummaryDto`)
  for a detail read, `list<JobPreviewDto>` for a list read whose total is
  only `count($items)` — the use case derives it there, so no page wrapper
  is declared — and Presentation formats an object, each response shape
  (7.10) declared once, in its Resource — 2026-09-25.
- **9.4** Eloquent is not swapped for a raw query builder to avoid hydrating
  models: hydration applies the casts the response depends on (`posted_at` →
  ISO-8601 through `immutable_datetime`, `min_salary` → `int`), while the
  builder would leak the database format into the API — 2026-09-25.
- **9.5** A "latest of many" relation is `hasOne(...)->orderByDesc($column)`,
  never `latestOfMany()`/`ofMany()`: the one-of-many subquery aggregates the
  primary key with `MAX(...)`, which PostgreSQL rejects for uuid keys
  (`function max(uuid) does not exist`). The ordered `hasOne` keeps the
  intended semantics — one model, and the eager-load matcher takes the first
  row of the ordered set per parent — 2026-09-25.
- **9.5a** Dates cross the layers as `DateTimeImmutable` and are formatted once
  in the Resource with `->format(DATE_ATOM)`; Carbon's `toJSON()`
  (`2026-05-24T13:33:59.000000Z`) is not used, so every endpoint emits the
  same `+00:00` form and the preview/detail formats cannot drift — 2026-09-25.
- **9.6** A DTO is named for the representation it carries, never for the
  route or method that returns it (`EmployerSummaryDto`, not
  `GetVacancyByIdEmployerDto`), and it is reused wherever that exact shape is
  needed: the same representation declared once per endpoint would drift,
  which 7.10 forbids. Reuse is decided by the field set, not by the number of
  consumers — as soon as one consumer needs one key more or one key less,
  that is a second DTO, never an extra nullable field on the shared one,
  because a shape widened to fit one more caller serves two masters (SRP) and
  makes the first consumer's contract untrue; a key is added to a shared DTO
  only when the consumers already using it need that key too
  — 2026-09-25.

## 10. Error handling

- **10.1** Standard (framework) exceptions are handled centrally in
  `bootstrap/app.php` (`withExceptions`): JSON rendering for `api/*`
  (`shouldRenderJsonWhen`) plus the framework's own mapping — Laravel's
  `ValidationException` → 422, `ModelNotFoundException` → 404,
  `QueryException` and any other framework throwable → 500. A controller
  never wraps these — 2026-09-25.
- **10.2** A domain exception is caught in the controller, and only as the
  exact class that endpoint expects — the detail route catches
  `VacancyNotFoundException` and answers
  `return new JsonResponse(['message' => $exception->getMessage()], 404)`,
  while a route whose use case cannot fail this way has no `try` at all.
  There is no catch-all: `catch (DomainException)`, `catch (\Exception)`,
  `catch (Throwable)` and any shared exception helper are forbidden (10.4),
  because an unexpected class is a bug and must surface as the framework's
  500 instead of being answered as if it were expected — 2026-09-25.
- **10.3** A validated uuid cannot reach `EntityId::fromString()`: the `uuid`
  rule is stricter than `Ramsey\Uuid\Uuid::isValid` (it rejects `{uuid}` and
  `urn:uuid:…`, the VO accepts them), so `InvalidUuidFormatException` stays a
  422 from the FormRequest and 10.2 needs it only if that rule is ever
  loosened — 2026-09-25.
- **10.4** The response is written inline in the `catch` block — one
  `new JsonResponse(['message' => $exception->getMessage()], $status)` per
  expected exception, no shared responder — so the status of every failure
  mode of an endpoint is visible where it is caught — 2026-09-25.
