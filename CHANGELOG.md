# Change Log

## 1.1.2 - 2023-05-10

### Fixed

- Fixed ServerRequestFactory not populating parsed body for PATCH and PUT requests when given application/x-www-form-urlencoded data.

## 1.1.1 - 2023-04-24

### Fixed

- Added error response code to response when error callback has an error.

## 1.1.0 - 2023-04-17

### Added

- Added basic PSR-18 client implementation.
- Response constructors now support int values for status.

### Fixed

- Fixed error with MessageTrait withAddedHeader function.

## 1.0.4 - 2023-04-14

### Fixed

- Fixed issues with MiddlewareManager callbacks.

## 1.0.3 - 2023-03-05

### Fixed

- Fixed prepend function not merging properly in MiddlewareManager.

## 1.0.2 - 2023-02-05

### Fixed

- Fixed issues with logging in MiddlewareManager.
- Fixed bad namespace in ErrorHandler.

## 1.0.1 - 2023-01-05

### Changed

- UriFactory now prioritizes HTTP\_HOST over SERVER\_NAME.

## 1.0.0 - 2022-12-27

Initial release.
