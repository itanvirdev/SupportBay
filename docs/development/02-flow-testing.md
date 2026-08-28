# Flow Testing

SupportBay flow tests exercise complete business workflows against a bootstrapped
WordPress installation. They use the configured local WordPress database and do
not call real provider APIs.

## Run the complete suite

Run the command with the PHP binary and `php.ini` used by the target WordPress
installation:

```bash
php tools/run-flow-tests.php all
```

Run one flow by replacing `all` with its registered flow key, for example:

```bash
php tools/run-flow-tests.php ticket
php tools/run-flow-tests.php security-authorization
php tools/run-flow-tests.php installation-lifecycle
```

The command exits with status `0` only when every selected flow passes. A failed
assertion or uncaught exception produces a non-zero exit status, making the
runner suitable for local automation and future continuous integration.

## Safety

- The runner is CLI-only.
- Flow tests still require `WP_DEBUG`, a local/development environment, and the
  explicit `SBAY_ENABLE_FLOW_TESTS` constant established by the runner.
- Browser requests cannot invoke the CLI runner.
- The legacy browser flow-test entry remains restricted to authenticated
  administrators with a valid nonce.
- Tests use fake providers and deterministic fixtures instead of external APIs.

## Current baseline

The complete MVP suite contains 38 passing flows covering tickets, messages,
activities, attachments, settings, authentication, providers, purchase
verification, portal REST APIs, React integration contracts, notifications,
database migrations, webhooks, authorization, and plugin lifecycle handling.
