# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


---

## [Unreleased]

## [0.4.0] – 2025-11-19

### Added
- Full **multiline string** support using triple-quoted blocks:
  ```toon
  bio: """
  I am Manoj.
  I love PHP and EVs.
  """

  Minification mode (EncodeOptions::setMinify(true)), producing compact TOON output while retaining valid structure.
  Extensive new PHPUnit tests:

deeply nested objects

mixed tabular + list + primitive arrays

multiline encode/decode

round-trip stability

EncodeOptions behaviour

minified output validation

Added strict type declarations everywhere.

Changed

Rewritten decoder with clearer indentation rules and better structural validation.

Cleaned and modularized encoder logic.

Improved handling of edge cases in lists, tabular arrays, and primitive arrays.

EncodeOptions moved to src/, now fully isolated from tests.

---

## [0.3.0] - 2025-11-17

### Added
- Multiline string decoding using `""" ... """`
- Nested object decoding using indentation
- Row-count validation for list and tabular arrays
- Better error messages with line numbers and context
- Full round-trip safety for nested + complex values

### Improved
- More stable parsing engine (recursive descent)
- More strict primitive/array handling
- PHPStan level 8 compatibility

### Tests
- Added multiline string test suite
- Added nested object tests
- Added deep round-trip stability tests
- 27 PHPUnit tests, 52 assertions — all passing

---


---

## [0.2.0] - 2025-11-14

### Added
- `EncodeOptions` for configurable indentation and optional trailing newline in `Toon::encode()`.
- Comment support in the decoder:
  - Full-line comments starting with `#` or `//`
  - Inline comments after values (e.g. `id: 1  # comment`)
- Validation for row-count mismatches:
  - Primitive arrays: `tags[3]: php,ai` → throws `DecodeException`.
  - List arrays: `tags[2]:` but only one `- value`.
  - Tabular arrays: `items[3]{...}:` but only two rows.
- PHPStan static analysis configuration (`phpstan.neon.dist`) and GitHub Actions CI step.
- Additional PHPUnit coverage, including round-trip tests (`decode(encode($data))`) and error-path tests.

### Changed
- Decoder error messages now include line numbers and more context for mismatches.
- Internal cleanup and stricter type hints to satisfy PHPStan.

---


---

## [0.1.0] - 2025-11-13

### Added
- Initial public release of **TOON PHP Lite**.
- `ToonLite\Toon::encode(mixed $data): string`
  - Encodes:
    - associative arrays → `key: value`
    - primitive lists → `key[n]: a,b,c`
    - uniform list of objects → `key[n]{col1,col2}:` tabular format
    - mixed/other lists → list format
- `ToonLite\Toon::decode(string $toon): mixed`
  - Supports:
    - `key: value`
    - `key[n]: a,b,c`
    - `key[n]{a,b,c}:` + tabular rows
    - `key[n]:` with `- value` items
- PHPUnit test suite.
- GitHub Actions CI for PHP 8.1, 8.2, 8.3.
