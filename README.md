![PHP Version](https://img.shields.io/badge/PHP-8.1%20|%208.2%20|%208.3-blue)
![Code Style](https://img.shields.io/badge/code%20style-PSR12-green)
![Static Analysis](https://img.shields.io/badge/PHPStan-Level%208-yellow)
![Coverage](https://img.shields.io/badge/tests-100%25-brightgreen)


# 🚀 TOON PHP Lite


**A lightweight, dependency-free PHP library for encoding & decoding TOON (Token-Oriented Object Notation).**

[![Tests](https://github.com/manojrammurthy/toon-php-lite/actions/workflows/tests.yml/badge.svg)](https://github.com/manojrammurthy/toon-php-lite/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/manoj/toon-php-lite.svg)](https://packagist.org/packages/manoj/toon-php-lite)
[![License](https://img.shields.io/github/license/manojrammurthy/toon-php-lite.svg)](LICENSE)

**TOON** is a compact, human-readable, LLM-friendly data format — an alternative to JSON that uses indentation, lists, and tabular rows for intuitive structure.

This library provides a **lite encoder + decoder**, suitable for everyday PHP applications, config parsing, and AI-driven workflows.

---

# ✨ Features

### ▶ Encoding & Decoding

* Simple API: `Toon::encode()` / `Toon::decode()`
* Full support for:

  * Nested objects via indentation
  * Primitive arrays:
    `tags[3]: php,ai,iot`
  * List arrays:

    ```toon
    tags[2]:
      - php
      - ai
    ```
  * Tabular arrays (`key[n]{a,b,c}:`):

    ```toon
    items[2]{sku,qty,price}:
      A1,2,9.99
      B2,1,14.5
    ```

### ▶ New in v0.4.0

* **Triple-quoted multiline strings**

  ```toon
  bio: """
  I am Manoj.
  I love PHP.
  """
  ```
* **Minified TOON output** (fully valid, no indentation)

  ```php
  (new EncodeOptions())->setMinify(true);
  ```
* **Complete test suite** + round-trip stability checks
* **PHPStan clean (level 8)**
* **Strict types everywhere**

### ▶ Other Features

* Zero dependencies
* Detailed decode errors

  * row count mismatches
  * bad tabular rows
  * invalid indentation
* Comment support:

  * `# comment`
  * `// comment`
  * `id: 1   # inline`

Supports PHP **8.1 – 8.3**

---

# 📦 Installation

### via Packagist

```bash
composer require manoj/toon-php-lite
```

### Development

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

```toon
id: 1
name: Manoj
tags[3]: php,ai,iot
items[2]{sku,qty,price}:
  A1,2,9.99
  B2,1,14.5
```

---

# 📘 API Reference

---

## `ToonLite\Toon::encode(mixed $data, EncodeOptions|int|null $options = null): string`

Encodes PHP values into valid TOON text.

### Supported mappings

#### **Objects**

```php
['id' => 1, 'name' => 'Manoj']
```

→

```toon
id: 1
name: Manoj
```

#### **Primitive arrays**

```php
['php', 'ai', 'iot']
```

→

```toon
tags[3]: php,ai,iot
```

#### **List arrays**

```php
['php', 'ai']
```

→

```toon
tags[2]:
  - php
  - ai
```

#### **Tabular arrays**

```php
[
    ['sku'=>'A1', 'qty'=>2, 'price'=>9.99],
    ['sku'=>'B2', 'qty'=>1, 'price'=>14.5],
]
```

→

```toon
items[2]{sku,qty,price}:
  A1,2,9.99
  B2,1,14.5
```

#### ✔ Multiline strings (NEW in v0.4.0)

```php
"Hello\nWorld"
```

→

```toon
bio: """
Hello
World
"""
```

#### ✔ Minify mode (NEW in v0.4.0)

```php
$opts = (new EncodeOptions())->setMinify(true);
echo Toon::encode($data, $opts);
```

Output:

```toon
id:1
name:"Manoj"
tags[3]:php,ai,iot
items[2]{sku,qty,price}:A1,2,9.99 B2,1,14.5
```

---

## `ToonLite\Toon::decode(string $toon): mixed`

Parses TOON back into PHP:

* associative arrays
* numeric arrays
* ints, floats, strings, bool, null
* multiline strings
* tabular & list arrays
* nested objects based on indentation

### Comments

```toon
# top-level comment
id: 1  # inline
// comment
name: Manoj
```

---

# 🔧 Development

### Run tests

```bash
vendor/bin/phpunit
```

### Static analysis

```bash
composer phpstan
```

### Code style (optional)

```bash
composer phpcs
composer php-cs-fixer
```

---

# 🗺 Roadmap

### Completed in v0.4.0

* [x] Multiline string support
* [x] Minified output
* [x] Strict decoder rewrite
* [x] 100% PHPUnit coverage
* [x] PHPStan level-8 clean
* [x] EncodeOptions overhaul

### Coming next

* [ ] Object-level annotations
* [ ] TOON schema validation
* [ ] Streaming decoder (very large files)
* [ ] Official TOON conformance tests
* [ ] Error messages with line/column numbers

---

# 📄 License

MIT © Manoj Ramamurthy

---


