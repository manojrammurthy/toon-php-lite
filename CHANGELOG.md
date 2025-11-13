# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- (planned) Better error messages with line/column info in `Decoder`.
- (planned) Support for more TOON constructs from the official spec.
- (planned) Configuration options for indentation and delimiters.
- (planned) Static analysis setup (PHPStan) and extended test suite.

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
- GitHub Actions CI (PHP 8.1, 8.2, 8.3).
