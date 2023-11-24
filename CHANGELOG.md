# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.4.0
### Added
- Add support for Symfony 6.x

## 1.3.0
### Added
- Support for `"php": "^8.0"`
- Support for `"phpunit/phpunit"": "^8.0"` and `"^9.0"`
### Removed
- Temporary removed `paysera/lib-php-cs-fixer-config` due to incompatibility with php 8.0
- Temporary removed `friendsofphp/php-cs-fixer` due to `paysera/lib-php-cs-fixer-config` incompatibility
- Temporary removed `cs-fixer` script from `.travis.yml`

## 1.2.2
### Removed
- `internal` tag from `CompilerPassProviderInterface`, `CompositeConfigurator`, `ConfiguratorInterface`, 
`ConfiguratorLoader`, `DefinitionsConfigurator`

## 1.2.1
### Remove
- Remove return types to avoid breaking changes;

## 1.2.0
### Added
- Added `"symfony/dependency-injection": "^5.0"` and `"symfony/config": "^5.0"` dependencies for Symfony 5 support;

## 1.1.0
### Added
- `AddTaggedCompilerPass` support for parameters with default values (associative array);
- `AddTaggedCompilerPass` support for priorities;
- `AddTaggedCompilerPass` support for call modes – ability to call method with service ID
or mark services as lazy.

### Changed
- Added support for Symfony 4
- Dropped support for PHP lower than 7.0
