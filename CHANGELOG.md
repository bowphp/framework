# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 5.2.96 - 2026-05-12

### What's Changed

* Fix migration by @papac in https://github.com/bowphp/framework/pull/384

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.95...5.2.96

### What's Changed

* Fix migration by @papac in https://github.com/bowphp/framework/pull/384
* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/385

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.95...5.2.96

## 5.2.95 - 2026-05-08

### What's Changed

* Fix data binding by @papac in https://github.com/bowphp/framework/pull/381
* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/382
* Optimize database query performance by @papac in https://github.com/bowphp/framework/pull/383

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.94...5.2.95

## 5.2.94 - 2026-04-07

### What's Changed

* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/379
* Fix many issues by @papac in https://github.com/bowphp/framework/pull/380

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.93...5.2.94

## 5.2.93 - 2026-04-05

### What's Changed

* Fix query builder by @papac in https://github.com/bowphp/framework/pull/377
* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/378

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.92...5.2.93

## 5.2.92 - 2026-04-04

### What's Changed

* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/374
* Add query and post method to request and fix nullable validator by @papac in https://github.com/bowphp/framework/pull/375

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.91...5.2.92

## 5.2.91 - 2026-03-28

### What's Changed

* Refactoring magic method definition by @papac in https://github.com/bowphp/framework/pull/372
* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/373

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.90...5.2.91

## 5.2.90 - 2026-03-20

### What's Changed

* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/370
* Fix query builder by @papac in https://github.com/bowphp/framework/pull/371

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.8...5.2.90

## 5.2.8 - 2026-03-20

### What's Changed

* Fix belongs to by @papac in https://github.com/bowphp/framework/pull/368
* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/369

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.7...5.2.8

## 5.2.7 - 2026-03-08

### What's Changed

* Add scheduler features by @papac in https://github.com/bowphp/framework/pull/365
* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/366
* Add missing http methods by @papac in https://github.com/bowphp/framework/pull/367

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.6...5.2.7

## 5.2.6 - 2026-02-27

### What's Changed

* Fix domain definition by @papac in https://github.com/bowphp/framework/pull/363
* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/364

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.5...5.2.6

## 5.2.5 - 2026-02-27

### What's Changed

* Fix database, validation, add rabbitmq/kafka queue adapter by @papac in https://github.com/bowphp/framework/pull/362

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.4...5.2.5

## 5.2.3 - 2026-01-27

### What's Changed

* Refactoring queue adapter and add redis support by @papac in https://github.com/bowphp/framework/pull/358

**Full Changelog**: https://github.com/bowphp/framework/compare/5.2.2...5.2.3

## [Unreleased]

### Added

- **SMTP Adapter**: Complete rewrite with RFC-compliant SMTP protocol implementation
  
  - Expanded from 8 to 21 methods for better functionality separation
  - Added comprehensive configuration validation (hostname, port, timeout)
  - Implemented multi-exception handling (SmtpException | SocketException)
  - Enhanced email address parsing supporting "Name [email@example.com](mailto:email@example.com)" format
  - Added optional authentication support
  - Created comprehensive test suite with 21 tests and 35 assertions
  
- **FTP Service**: Connection retry logic with 3 attempts and configurable delays
  
- **FTP Service**: Configuration constants and validation for all required fields
  
- **FTP Service**: Automatic stream cleanup with try-finally blocks
  
- **FTP Service**: Destructor for proper resource cleanup
  
- **Database Notifications**: Enhanced test coverage with 4 additional comprehensive tests
  
- **Queue System**: Graceful logger fallback in BeanstalkdAdapter
  

### Changed

- **FTP Service**: Complete refactoring with improved error handling and resource management (651 lines)
  
  - Enhanced all file operations methods (store, get, put, append, prepend, copy, move, delete)
  - Improved directory operations (files, directories, makeDirectory)
  - Better passive/active mode configuration
  - More specific and actionable error messages
  - Added connection state validation with `ensureConnection()` method
  
- **Environment Configuration**: Fixed path handling by removing unreliable `realpath()` usage
  
- **Configuration Loader**: Improved validation and error handling
  
- **Notifier System**: Fixed PHPUnit mock issues and corrected type signatures
  
- **Test Suite**: Renamed test methods to snake_case for consistency
  
- **Database Tests**: Significantly expanded test coverage across connection, migration, pagination, and query builders
  

### Fixed

- **SMTP Adapter**: Port validation now correctly validates range (1-65535)
- **SMTP Adapter**: Timeout validation now requires positive integers
- **FTP Service**: Fixed directory listing parser to handle filenames with spaces
- **FTP Service**: Improved error messages with connection details
- **Environment Configuration**: Fixed `Env::configure()` error handling
- **Queue Tests**: Fixed mock configuration issues in NotifierTest
- **Notification Tests**: Added missing timestamp columns in test schema

### Improved

- **Test Coverage**: Added 29 new tests with 46 new assertions
- **Error Rate**: Reduced test errors by 39% (28 → 17 errors)
- **Failure Rate**: Reduced test failures by 70% (10 → 3 failures)
- **Code Quality**: Better error messages across all refactored components
- **Resource Management**: Proper cleanup prevents resource leaks
- **Configuration Validation**: Early validation with specific error messages

## 5.1.7 - 2024-12-21

### What's Changed

* Update CHANGELOG by @papac in https://github.com/bowphp/framework/pull/305
* feat(barry): add relative create method for barry model by @papac in https://github.com/bowphp/framework/pull/306

**Full Changelog**: https://github.com/bowphp/framework/compare/5.1.6...5.1.7

## 5.1.6 - 2024-12-20

### What's Changed

* Implement feature for improve http and str classes by @papac in https://github.com/bowphp/framework/pull/304

**Full Changelog**: https://github.com/bowphp/framework/compare/5.1.5...5.1.6

## 5.1.2 - 2023-09-17

Fix `app_db_seed` helper

Ref

- #257
- #256

## 5.1.1 - 2023-08-21

Add the transaction method

This method aims to execute an SQL transaction around a passed arrow function.

```php
Database::transaction(fn() => $user->update(['name' => '']));












```
Ref: #255

## 5.1.0 - 2023-06-07

Release 5.1.0

- Add custom adaptor #252
- Make Redis accessible on whole project #250

## 5.0.9 - 2023-06-01

Release 5.0.9

Fixes many bugs

Reference #248

## 5.0.8 - 2023-05-24

Release **5.0.8**
Fixes test case errors

Reference #243
From #242

## 5.0.7 - 2023-05-24

Release **5.0.7**

- Fixes the database relationship
- Fixes the HTTP client
- Fixes the JWT authentication service

Fixes #241
Fixes #213
Fixes #240

## 5.0.6 - 2023-05-22

Release **5.0.6**

- Fixes get last insert id for pgsql
- Add data validation custom message parser
- Fixes PgSQL migration errors
- Fixes initialize the request ID #236

References

- Validation and PgSQL #237
- Many bugs fixes #237

## 5.0.5 - 2023-05-20

Release **5.0.5**

- Fix migration status table definition
- Fix enum creation for pgsql

Reference #232

## 5.0.4 - 2023-05-19

Release **5.0.4**

- Fixes HTTP Client
- Add variable binding to the env loader
- Fixes validation for regex rule
- Fixes request data parser
- Fixes middleware execution order

All update ref #230

## 5.0.3 - 2023-05-16

Add many fixes

- Fixes the error handler
- Fixes the HTTP client
- Fixes TestCase service

## 5.0.2 - 2023-05-16

Release **5.0.2**

- Fix action dependency injector
- Add the base error handler

## 5.0.0 - 2023-05-10

- [Add] Convert the project from PHP7 to PHP8
- [Add] Multi connection for storage system
- [Fix] Fixes migrations errors [#176](https://github.com/bowphp/framework/pull/176)
- [Fix] Fixes the column size [#165](https://github.com/bowphp/framework/pull/165)
- [Fix] Add the fallback on old request method [#170](https://github.com/bowphp/framework/pull/170)
- [Fix] Define the default value on migration [#162](https://github.com/bowphp/framework/pull/162)
- [Change] Refactoring http request [#194](https://github.com/bowphp/framework/pull/194)
- [Add] Add ability to identify the incomming request by id [#195](https://github.com/bowphp/framework/pull/195)
- [Remove] Remove the helpers ftp, s3
- [Add] Add `storage_service` helper for load any storage services
