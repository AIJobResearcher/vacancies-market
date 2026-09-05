# Code Standards

Style and static-analysis rules for the Vacancies Market service. The agent is
not expected to carry these in context: `vendor/bin/pint`, `vendor/bin/phpcs`,
`vendor/bin/phpstan` and `vendor/bin/psalm` enforce the formal parts; follow the
rest when writing code.

## File structure

- PHP files: `<?php declare(strict_types=1);` on the second line (blank line
  after `<?php`). End file with one blank line.
- PSR-12 formatting: method names `camelCase`, braces on new lines, no line
  over 120 chars, parentheses required for `new` even without args, no unused
  imports.
- No prose comments in code; PHPDoc only for precise types where analyzers
  require them.

## Eloquent models

- Add `@property` / `@property-read` for all magic properties.
- Type-hint relations with `@return` and `@param` where needed.

## Typing and docblocks

- Annotate arrays: `@var`, `@param`, `@return` with shapes (e.g.
  `array<int, string>`); never use `mixed` in type hints or docblocks.
- PHPDoc: valid syntax, no blank lines between annotations, use only for
  precise types.
- Handle nullable values: use `??`, null checks, or default values before
  passing to non-nullable parameters.
- Explicitly cast values when needed (e.g. `(int) $model->version`,
  `$model->external_urls ?? []`); avoid redundant casts.
- Avoid redundant assertions or checks that static analysis flags as always
  true/false.

## Class design

- Add `#[Override]` to all methods overriding parent methods.
- Mark classes `final` unless inheritance is intended.
- No unused classes, methods, properties, or imports; remove or use them.
- Use readonly properties and immutable value objects where possible.
- Keep Domain free of Eloquent models, DTOs, and infrastructure details.
- Write self-documenting code: intention-revealing names, no need for
  explanatory comments.
