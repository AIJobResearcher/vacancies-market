# PHP Code Standards

## File structure

- PHP files: `<?php declare(strict_types=1);` on the second line (blank line
  after `<?php`). End file with one blank line.
- PSR-12 formatting: method names `camelCase`, braces on new lines, no line
  over 120 chars; length counts the whole line including leading indentation,
  not just the code after the indents. Parentheses required for `new` even
  without args.
- Keep the `use` block in sync with every file: when you add or remove a
  class/usage, add or remove its `use` statement too; no unused imports.

## Docblocks and comments

- No prose comments or descriptions on classes, methods, or properties; no
  explanatory comments in code.
- PHPDoc only for precise types where analyzers require them.

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

- Order class members as: constants → properties (public, then protected,
  then private) → constructor → magic methods → public methods → protected
  methods → private methods (non-static before static in each; alphabetical).
  Within a method group: getters first, then setters, then others, alphabetical.
- Add `#[Override]` to all methods overriding parent methods.
- Mark classes `final` unless inheritance is intended.
- No unused classes, methods, properties, or imports: if you create one that is
  not used yet, mark it with `@psalm-suppress PossiblyUnusedMethod` /
  `PossiblyUnusedClass`; remove that suppression the first time it is used.
- Use readonly properties and immutable value objects where possible.
- Write self-documenting code: intention-revealing names, no need for
  explanatory comments.
