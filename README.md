# 🚀 TOON PHP Lite

**A lightweight, dependency-free PHP library for encoding & decoding TOON (Token-Oriented Object Notation).**

[![Tests](https://github.com/manojrammurthy/toon-php-lite/actions/workflows/tests.yml/badge.svg)](https://github.com/manojrammurthy/toon-php-lite/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/manoj/toon-php-lite.svg)](https://packagist.org/packages/manoj/toon-php-lite)
[![License](https://img.shields.io/github/license/manojrammurthy/toon-php-lite.svg)](LICENSE)

TOON is a compact, human-readable, LLM-friendly data format — an alternative to JSON designed to be easier for large language models to parse.

This library implements a **lite encoder + decoder** for everyday PHP projects.

---

## ✨ Features

* ✔ Simple API: `Toon::encode()` / `Toon::decode()`
* ✔ Supports:
  * objects (top-level key/value pairs)
  * primitive arrays: `tags[3]: php,ai,iot`
  * list arrays: 
    ```toon
    tags[2]:
      - php
      - ai
    ```
  * tabular arrays (`key[n]{a,b,c}:` rows)
* ✔ Comment support in decoder:
  * full-line `#` / `//` comments
  * inline comments after values (`id: 1  # note`)
* ✔ Row-count validation:
  * throws `DecodeException` when declared count `[N]` doesn’t match actual items
* ✔ Configurable encoding via `EncodeOptions` (indent size, trailing newline)
* ✔ Zero external runtime dependencies
* ✔ Fully unit-tested (PHPUnit)
* ✔ PHPStan static analysis + GitHub Actions CI
* ✔ PHP 8.1 – 8.3 supported

---


# 📦 Installation

# from packagist 

```bash
composer require manoj/toon-php-lite
```

For local development:

```bash
git clone git@github.com:manojrammurthy/toon-php-lite.git
cd toon-php-lite
composer install
```

---

# 🚀 Quick Start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ToonLite\Toon;

$data = [
    'id'    => 1,
    'name'  => 'Manoj',
    'tags'  => ['php', 'ai', 'iot'],
    'items' => [
        ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
        ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
    ],
];

$toon = Toon::encode($data);
echo $toon;

$decoded = Toon::decode($toon);
var_dump($decoded);
```

### Output

```
id: 1
name: Manoj
tags[3]: php,ai,iot
items[2]{sku,qty,price}:
  A1,2,9.99
  B2,1,14.5
```

---

# 📘 API Reference

ToonLite\Toon::encode(mixed $data, ?EncodeOptions $options = null): string

Encodes PHP values into TOON.

Mapping rules:

Associative arrays →
key: value

Primitive lists →
key[n]: a,b,c

Uniform object lists →
key[n]{col1,col2}:

val1,val2
val3,val4

Mixed lists →

key[n]:
  - value
  - other


---

## `ToonLite\Toon::decode(string $toon): mixed`

ToonLite\Toon::decode(string $toon): mixed

Parses TOON back into PHP:

associative arrays (array<string,mixed>)

numeric arrays

primitives (int, float, bool, string, null)

Supported syntax:

key: value

key[n]: a,b,c

key[n]{a,b,c}: followed by tabular rows

key[n]: + - value list items

Comments:

# comment

// comment

inline id: 1 # this is ignored

# 🛠 Development

Run tests:

```bash
vendor/bin/phpunit
```

---

# 🗺 Roadmap

* [ ]  Nested object decoding using indentation (e.g. user: … blocks)
* [ ]  Full TOON spec coverage (advanced blocks)
* [ ]  Better error handling with line/column numbers
* [ ] Minified TOON output option
* [ ] Integration with official TOON conformance tests

---

# 📄 License

MIT © Manoj Ramamurthy

---
