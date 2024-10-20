# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 - 2024-10-20

### Added

- **Integrated official DeepL API package for translation services and refactored the entire package logic.**<br />
  The package now directly leverages the official DeepL PHP client for all translation functionality, streamlining the integration and improving maintainability. All methods have been adjusted to delegate tasks to the DeepL client, ensuring a more efficient and consistent translation process throughout the application.
- On-the-fly translation feature with support for queuing translations via custom translator registration.
- Added chainable methods for translating texts, documents, creating glossaries, and managing supported languages.

### Changed

- Refactored the entire package to use the official DeepL API client
- Improved Minor Modifications
- Improved Usage Command
- Improved Tests
- Updated Readme.md
- Improved Translation Cache - you can set the table name in the config file

### Removed

- Unused code

## 0.2.1 - 2024-10-13

### Changed

- Updated Readme.md

## 0.2.0 - 2024-10-13

### Added

- Command for translating entire localization folders

## 0.1.1 - 2024-10-12

### Changed

- Updated Readme.md

### Fixed

- Service Provider

## 0.1.0 - 2024-08-18

### Added

- Initial release of the Laravel DeepL package

