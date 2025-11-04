# Priority Queue Helper

`src/Priority.php` exposes a lightweight helper for queuing values with named priority levels and retrieving them in order. It is useful for assembling ordered lists (HTML snippets, middleware, hooks) where consumers prefer readable priority keywords instead of raw integers.

## Key Features

- Supports configurable priority labels (e.g., `veryfirst`, `normal`, `verylast`). Accepts arrays or comma-delimited strings.
- Provides dynamic methods (`addNormal()`, `addHigh()`, etc.) via `__call`, plus a fallback `add()` method that uses the "in the middle" priority.
- Generates ordered output as concatenated text (`text()`), array (`array()`), or JSON (`json()`).
- Optional deduplication (`no duplicates` config) to ensure the same value is queued only once.
- Preserves insertion order within the same priority bucket using high-resolution timestamps.

## Configuration

Pass a config array to the constructor:

```php
use peels\priority\Priority;

$priority = new Priority([
    'no duplicates' => true,
    'priorities' => ['before', 'normal', 'after'],
]);
```

- `no duplicates` (`bool`): When true, values are hashed and only the first occurrence is stored.
- `priorities` (`array|string`): Override the default 12-level priority scale. Values are converted to weights internally.

## Basic Usage

```php
$priority->addHigh('footer');
$priority->addLow(['header', 'nav']);
$priority->add('body'); // defaults to "normal"

echo $priority->text();     // concatenated string
$priority->array();         // ordered array of values
$priority->json();          // JSON encoded array
```

Dynamic methods are constructed from `add` + priority name (case-insensitive). Attempting to use an unknown priority throws an exception.

## Implementation Notes

- Internally, values are stored with a composite float key: `priority weight . hrtime(true)`. This ensures stable ordering even when multiple items share the same priority.
- Default priorities span from `veryfirst` to `verylast`; update the configuration if you need a narrower or different set.
