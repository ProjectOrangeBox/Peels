# Auth Package Overview

The `peels/auth` package provides a lightweight authentication service that handles basic credential verification and state management. The source files live under `src/` and are organised as follows:

- `src/Auth.php`: Concrete implementation of the authentication workflow. It merges configuration defaults, validates user credentials against a PDO connection, tracks the active user ID, and exposes helper methods such as `login()`, `logout()`, and `hasError()`.
- `src/AuthInterface.php`: Interface contract defining the public API required by any authentication implementation (`login`, `logout`, `userId`, `error`, and `hasError`).
- `src/config/auth.php`: Default configuration array consumed by `Auth`. It declares table/column names for database lookups and user-facing error messages. Override these values when wiring the package into your project.

To use the package, load the configuration (optionally merging in your own overrides), pass an active `PDO` instance, and either instantiate `Auth` directly or retrieve the shared singleton via `Auth::getInstance()`. After a successful `login()`, the authenticated user's identifier is available through `userId()`. On failures, inspect `hasError()` and `error()` to surface meaningful feedback.

> **Note:** Ensure the configured columns exist in your backing table and that passwords are stored as hashes compatible with PHP's `password_verify()` function.
