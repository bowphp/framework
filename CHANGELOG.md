# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 5.0.9 - 2023-06-01

Release 5.0.9

Fixes many bugs

Reference #248

## 5.0.8 - 2023-05-24

Release 5.0.8

Fixes test case errors

Reference #243
From #242

## 5.0.7 - 2023-05-24

Release 5.0.7

- Fixes the database relationship
- Fixes the HTTP client
- Fixes the JWT authentication service

Fixes #241
Fixes #213
Fixes #240

## 5.0.6 - 2023-05-22

Release 5.0.6

- Fixes get last insert id for pgsql
- Add data validation custom message parser
- Fixes PgSQL migration errors
- Fixes initialize the request ID #236

References

- Validation and PgSQL #237
- Many bugs fixes #237

## 5.0.5 - 2023-05-20

Release 5.0.5

- Fix migration status table definition
- Fix enum creation for pgsql

Reference #232

## 5.0.4 - 2023-05-19

Release 5.0.4

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
