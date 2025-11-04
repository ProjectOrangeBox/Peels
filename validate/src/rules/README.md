# Validation Rules Overview

The classes in this directory define how the validation engine loads, configures, and runs individual rules and filters.

## Files

- `Rules.php`: Registry responsible for discovering and instantiating rule classes based on configuration aliases.
- `RuleAbstract.php`: Base class providing shared helpers (option handling, error messaging, access to `ValidateInterface`) that concrete rules extend.
- `Cast.php`: Support rule that casts incoming values to a specific scalar or array type before further validation.
- `Filters.php`: Collection of reusable filters (trim, lowercase, etc.) that can be applied by `Filter.php` prior to validation.

Each rule leverages `RuleAbstract` for consistent access to input data and configuration. Extend `RuleAbstract` when adding new validation rules, register them via `Rules.php`, and reference the alias in your validation definitions.
