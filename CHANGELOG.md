# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 5.0.2 - 2023-05-16

Release for 5.0.2

- Fix action dependency injector
- Add the base error handler

## 5.0.0 - 2023-05-10

- [Add] Convert the project from PHP7 to PHP8
- [Add] Multiconnection for storage system
- [Fix] Fixes migrations errors [#176](https://github.com/bowphp/framework/pull/176)
- [Fix] Fixes the column size [#165](https://github.com/bowphp/framework/pull/165)
- [Fix] Add the fallback on old request method [#170](https://github.com/bowphp/framework/pull/170)
- [Fix] Define the default value on migration [#162](https://github.com/bowphp/framework/pull/162)
- [Change] Refactoring http request [#194](https://github.com/bowphp/framework/pull/194)
- [Add] Add ability to identify the incomming request by id [#195](https://github.com/bowphp/framework/pull/195)
- [Remove] Remove the helpers ftp, s3
- [Add] Add `storage_service` helper for load any storage services
