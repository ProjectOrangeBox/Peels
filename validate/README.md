# Validate Package Overview

The `peels/validate` module supplies a flexible validation pipeline that can filter, remap, and verify structured data. Source code lives in `src/` with dedicated subdirectories for configuration, interfaces, reusable rules, and exceptions.

## Core Classes (`src/`)

- `Validate.php`: Entry point orchestrating rule execution, remapping, and error collection. Implements `ValidateInterface`.
- `Filter.php`: Applies filter chains to incoming data, driven by definitions in `rules/Filters.php`.
- `Remap.php`: Transforms input structures into desired output shapes using notation helpers.
- `Notation.php`, `WildNotation.php`: Helpers for addressing nested data using dot and wildcard syntax.
- `ValidJson.php`: Convenience wrapper for validating JSON payloads with the existing rule engine.
- `ValidationError.php`: Value object representing a single validation failure (message, field, context).

## Configuration (`src/config/`)

- `validate.php`: Default configuration array mapping rule names to class implementations and providing baseline filter/remap options. Override or extend this file when integrating the package.

## Interfaces (`src/interfaces/`)

- `ValidateInterface.php`: Contract for the main validation service.
- `FilterInterface.php`: Defines filter behaviour expected by filter implementations.
- `RemapInterface.php`: Describes the API for mapping/transformation services.

## Rules (`src/rules/`)

- `Rules.php`: Registry/loader for available validation rules.
- `RuleAbstract.php`: Base class supplying common rule plumbing (messages, option handling).
- `Cast.php`: Utility rule for casting values to a specified type.
- `Filters.php`: Collection of built-in filters used by `Filter.php`.

## Exceptions (`src/exceptions/`)

- `ValidateException.php`: Base exception for general validation errors.
- `ValidationFailed.php`: Thrown when validation halts due to unrecoverable issues.
- `RuleFailed.php`: Signals an individual rule failure when a more specific exception is needed.
- `RuleNotFound.php`: Raised if a configured rule alias cannot be resolved.
- `InvalidValue.php`: Used when incoming data cannot be processed by a rule or filter.

## Usage Notes

1. Merge the default configuration with your application setup, registering custom rules or filters by class name.
2. Instantiate `Validate` (or your class implementing `ValidateInterface`) with the merged config.
3. Build validation definitions referencing rule aliases from `rules/` and optional remap/filter instructions.
4. Catch `ValidationFailed` to access `ValidationError` instances for user feedback.

Refer to the examples in `tests/` for end-to-end usage patterns, and extend the `rules/` directory with bespoke logic as your domain requires.
