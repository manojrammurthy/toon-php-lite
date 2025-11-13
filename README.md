Great start — but your current README.md needs:

* proper **Markdown formatting**
* closing code fences
* fixed section headers
* bullet lists turned into real Markdown lists
* spacing and layout improvements
* better title + description line
* syntax highlighting for PHP
* consistent code blocks

I will **clean it up completely**, make it look professional, and ready for Packagist + GitHub.

Here is the improved, polished, production-ready `README.md`:

---

# 🚀 TOON PHP Lite

**A lightweight, dependency-free PHP library for encoding & decoding TOON (Token-Oriented Object Notation).**

[![Tests](https://github.com/manojrammurthy/toon-php-lite/actions/workflows/tests.yml/badge.svg)](https://github.com/manojrammurthy/toon-php-lite/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/manoj/toon-php-lite.svg)](https://packagist.org/packages/manoj/toon-php-lite)
[![License](https://img.shields.io/github/license/manojrammurthy/toon-php-lite.svg)](LICENSE)

TOON is a compact, human-readable, LLM-friendly data format — an alternative to JSON designed to be easier for large language models to parse.

This library implements a **lite encoder + decoder** for everyday PHP projects.

---

# ✨ Features

* ✔ Simple API: `Toon::encode()` / `Toon::decode()`
* ✔ Supports:

  * objects
  * nested objects
  * primitive arrays
  * list arrays
  * tabular arrays (`key[n]{a,b,c}: ...`)
* ✔ Zero external dependencies
* ✔ Fully unit-tested (PHPUnit)
* ✔ GitHub Actions CI included
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

## `ToonLite\Toon::encode(mixed $data): string`

Encodes PHP values into TOON.

Mapping rules:

* **Associative arrays** →
  `key: value`
* **Primitive lists** →
  `key[n]: a,b,c`
* **Uniform object lists** →
  `key[n]{col1,col2}:`
    `val1,val2`
* **Mixed lists** →
  `key[n]:`
    `- value`

---

## `ToonLite\Toon::decode(string $toon): mixed`

Parses a subset of the TOON specification back into:

* associative arrays
* numeric arrays
* primitive values (int, float, bool, string)

Supported syntax:

* `key: value`
* `key[n]: a,b,c`
* `key[n]{a,b,c}:` followed by tabular rows
* `key[n]:` + `- value` items

---

# 🛠 Development

Run tests:

```bash
vendor/bin/phpunit
```

---

# 🗺 Roadmap

* [ ] Full spec coverage (advanced TOON blocks)
* [ ] Better error handling with line/column numbers
* [ ] Configurable indentation + delimiters
* [ ] Minified TOON output option
* [ ] Integration with official TOON conformance tests

---

# 📄 License

MIT © Manoj Ramamurthy

---

## ✅ Paste this directly into your README.md

Would you like:

* a **Packagist submission guide**,
* a **0.1.0 release tagging guide**, or
* better **project branding** (logo, icon, tagline)?
