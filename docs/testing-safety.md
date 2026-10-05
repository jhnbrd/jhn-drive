# Testing Safety Baseline

JHN Drive stores real user data on the local filesystem, so its automated test
environment is intentionally fail-closed.

## Phase 0 baseline

Before Phase 0, PHPUnit inherited the normal SQLite database because the
in-memory settings were commented out. Some tests truncated tables, deleted
users, wrote to the configured drive, or used the hardcoded Secret Vault path.

Phase 0 isolates both persistence layers:

- PHPUnit forces SQLite `:memory:` for every test process.
- The configured test drive is restricted to `storage/framework/testing`.
- Laravel's `Storage::fake('local_drive')` provides a fresh disk for each
  feature test.
- Database migrations run only after the base test case verifies the testing
  environment, database driver, database name, and drive root.
- The PHPUnit bootstrap refuses to start when required safety variables are
  missing or overridden.
- Secret Vault files resolve through `local_drive`, so vault tests use the same
  isolated fake disk.

The normal `.env` file, `database/database.sqlite`, and
`storage/app/drive_storage` must never be used by automated tests.

## Running tests

Use the repository PHPUnit configuration:

```bash
php artisan test
```

Do not add `RefreshDatabase` to a feature test. Its setup hook can run before
the base test case performs its fail-closed safety checks. The base test case
already creates a fresh in-memory schema for each feature test.

`.env.testing.example` documents the expected values for tools that require a
testing environment file. PHPUnit itself enforces the same values in
`phpunit.xml`.

## Verified Phase 0 baseline

- The isolated suite passes 20 tests with 86 assertions.
- Before/after verification confirms that the real SQLite database hash and
  real drive metadata remain unchanged during the suite.
- All project PHP files pass syntax checks.
- The Vite production build, route listing, and Composer validation succeed.
- Running a feature test without the PHPUnit configuration fails closed before
  migrations or test storage setup.
- PHP 8.5 reports a deprecation for Laravel's MySQL PDO constant even though the
  test database is SQLite. This warning does not fail the suite and remains for
  the stabilization phase.
