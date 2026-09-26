# Vacancies Market

## Testing

The project uses **PHPUnit** with three test suites:

| Suite | Location | Purpose |
| ------- | ---------- | --------- |
| **Unit** | `tests/Unit/` | Pure business logic (domain entities, value objects, services) |
| **Feature** | `tests/Feature/` | Application layer, HTTP endpoints, commands |
| **Integration** | `tests/Integration/` | Database, outbox, queues (real infrastructure) |

### Running Tests

All tests are executed inside the Docker container. Use the provided
`Makefile` targets:

| Command | Description |
| --------- | ------------- |
| `make test` | Run Unit, Feature and Integration tests **plus all static analysis** |
| `make test-unit` | Run only Unit tests (fast, no database) |
| `make test-feature` | Run only Feature tests |
| `make test-integration` | Run Integration tests against PostgreSQL (requires `make db-test-prepare` once) |

**Example workflow:**

```bash
make up                # start containers
make test-unit         # quick sanity check
make db-test-prepare   # create test DB (one time)
make test-integration  # full integration suite
make test              # all tests
```

### What You Will See

- **Green/red** output with test names and execution time.
- **PHPUnit summary** at the end (e.g., `OK (10 tests, 20 assertions)`).
- For integration tests, **database transactions** are rolled back automatically.
- Coverage reports (if enabled) are written to `coverage/` and `coverage-integration/`.

### Code Coverage

To generate coverage reports (requires Xdebug or PCOV):

```bash
make test-coverage              # Unit/Feature coverage
make test-coverage-integration  # Integration coverage
```

Reports are available as HTML in the corresponding directories.

### Configuration

- `phpunit.xml` – Main config (Unit + Feature, SQLite)
- `phpunit.integration.xml` – Integration tests (PostgreSQL)
- `.env.testing` – Environment overrides for testing

## Static Analysis

Code quality is also enforced by **PHPStan**, **Psalm**, **PHP_CodeSniffer**,
**Deptrac** (architectural dependency rules) and **markdownlint-cli2**. All run
inside the Docker container via `Makefile` targets:

| Command | What it does |
| --------- | -------------- |
| `make test-phpstan` | Runs PHPStan (`level 10`) static analysis |
| `make test-psalm` | Runs Psalm (`errorLevel 1`) analysis |
| `make test-phpcs` | Runs PHP_CodeSniffer (PSR‑12 + Slevomat ruleset) |
| `make test-phpcs-fix` | Auto‑fixes fixable PHP_CodeSniffer violations (`phpcbf`) |
| `make test-deptrac` | Checks architectural dependency rules (Clean Architecture) |
| `make test-static` | Runs all of the above in one go |
| `make test-md` | Lints all committed Markdown files (`markdownlint-cli2`) |

**Run everything (single target):**

```bash
make up
make test-static
```

**Run a single tool:**

```bash
make test-phpstan
make test-psalm
make test-phpcs
make test-deptrac
```

`make test-phpcs-fix` will automatically fix what it can — run it and then
re‑check with `make test-phpcs`:

```bash
make test-phpcs-fix
make test-phpcs
```

Each tool reads its own config at the repo root: `phpstan.neon`, `psalm.xml`,
`phpcs.xml.dist`, `deptrac.yaml`.
