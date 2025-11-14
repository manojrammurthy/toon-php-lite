# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.2.0] - 2025-11-14

### Added
- `EncodeOptions` for configurable indentation and optional trailing newline in `Toon::encode()`.
- Comment support in the decoder:
  - Full-line comments starting with `#` or `//`
  - Inline comments after values (e.g. `id: 1  # comment`)
- More informative `DecodeException` messages including line numbers and offending lines.

### Fixed
- Minor internal consistency between encoder/decoder around indentation handling.


## [Unreleased]

### Planned for v0.3.0
- Improve `Decoder` error messages with line and column information.
- Validate row-count mismatches (e.g., `items[3]` but only 2 rows provided).
- Add `EncodeOptions` class (indent size, trailing newline, future formatting options).
- Add PHPStan static analysis (`phpstan.neon.dist`) and CI integration.
- Expand PHPUnit test coverage:
  - deeply nested values
  - strings with commas/quotes/colons
  - empty arrays and objects
  - full round-trip tests (`decode(encode(data))`)
- Internal cleanup and stricter type hints.

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
