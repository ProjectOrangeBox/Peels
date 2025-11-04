# Bitwise Flag Manager

`BitWise.php` provides a fluent API for defining and working with named bitwise flags. Instead of tracking raw integers, you can register symbolic names and toggle them using expressive helpers.

## Core Features

- Register bits with human-readable names (`addBit`, `addBits`).
- Turn individual or multiple bits on/off (`turnOn`, `turnOff`), reset state, and check status (`isSet`, magic `__get`).
- Retrieve bit definitions or integer masks (`hasBit`, `getInt`) with helpful exceptions when references are invalid.
- Built-in constants for `ALWAYS`, `NONE`, and `EVERYTHING` to simplify common checks.
- Chainable methods for concise flag management flows.

## Exception Handling

- `exceptions/Bitwise.php` defines `BitNotFound`, thrown when code references a bit that has not been registered.

## Usage Overview

```php
use peels\bitwise\BitWise;

$bitwise = new BitWise(['error', 'warning']);
$bitwise->turnOn('error');

if ($bitwise->warning) {
    // flag still off
}

$bitwise->turnOn('warning')->turnOff('error');

if ($bitwise->isSet('warning')) {
    // perform logic
}
```

## Testing

The `unittest/` folder contains example tests you can adapt for integrating the class into your own suite.
