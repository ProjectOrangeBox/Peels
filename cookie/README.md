# Cookie Package Overview

The `peels/cookie` module wraps HTTP cookie handling behind a small interface so you can swap input/output implementations while keeping consistent behaviour. All source files are located in `src/` with accompanying configuration.

## Core Classes (`src/`)

- `Cookie.php`: Singleton service that reads cookies from an `InputInterface`, writes cookies via an `OutputInterface`, and merges configuration defaults through `ConfigurationTrait`. It exposes helper methods for `get`, `has`, `set`, and `remove`.
- `CookieInterface.php`: Contract outlining the cookie API, allowing your application to depend on the interface instead of the concrete implementation.

## Configuration (`src/config/`)

- `cookie.php`: Default settings used when generating cookie headers (path, domain, secure, HttpOnly, SameSite). Merge or override these values to align with your deployment requirements.

## Usage Notes

Instantiate `Cookie` (or obtain it via the provided singleton) with your framework's input/output services. Use `set()` to queue cookies for the response, `get()`/`has()` to read incoming values, and `remove()` to clear cookies. When overriding defaults, update `config/cookie.php` or supply your own configuration array during construction.
