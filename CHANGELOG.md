# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Applied Rector (code quality / type declarations): repeated strict comparisons collapsed into `in_array(..., true)`, and explicit parameter/return types added to arrow functions. No behaviour change.
- GitHub Actions: `actions/checkout` bumped to v5 and `softprops/action-gh-release` to v2.

## [v1.4.0] - 2026-09-17

### Added
- Laravel 13 support (`illuminate/support` and `illuminate/contracts` `^12.19|^13.0`).
- Symfony 8 support for `symfony/yaml` (`^7.3|^8.0`).
- CI test matrix now covers PHP 8.3 / 8.4 with Laravel 12 (testbench 10) and Laravel 13 (testbench 11), in `prefer-lowest` and `prefer-stable` modes.

### Changed
- PHP 8.3 is the minimum supported version.
- Dev dependencies updated: `orchestra/testbench` `^10.0|^11.0`, `pestphp/pest` `^3.8|^4.0`, `pestphp/pest-plugin-laravel` `^3.2|^4.0`.
- Release workflow now runs against Laravel 13.
- README badges and requirements updated for Laravel 12 / 13.

### Fixed
- `rector.php`: removed the `strictBooleans` prepared set, which no longer exists in Rector 2.x (the rules are part of `codeQuality`), so `composer full` runs again.

## [v1.3.0] - 2025-07-20

Previous release. See the [GitHub releases](https://github.com/Grazulex/laravel-safeguard/releases) for details.

## [v1.2.2] - 2025-07-18

Previous release. See the [GitHub releases](https://github.com/Grazulex/laravel-safeguard/releases) for details.

[Unreleased]: https://github.com/Grazulex/laravel-safeguard/compare/v1.4.0...HEAD
[v1.4.0]: https://github.com/Grazulex/laravel-safeguard/compare/v1.3.0...v1.4.0
[v1.3.0]: https://github.com/Grazulex/laravel-safeguard/compare/v1.2.2...v1.3.0
[v1.2.2]: https://github.com/Grazulex/laravel-safeguard/releases/tag/v1.2.2
