# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.3.7] - 2021-04-11

- [fix] Fix authentication guard "Illegal string offset [type]"

## [4.3.2] - 2020-11-16

- [fix] JWT authentication failed [a8fdf4](https://github.com/bowphp/framework/commit/a8fdf4332e5ef1df5585734a72a947e20c8982f7)

## [4.3.0] - 2020-09-13

- [fix] fix [#97](https://github.com/bowphp/framework/issues/97)
- [fix] fix [#96](https://github.com/bowphp/framework/issues/96)
- [fix] fix [#95](https://github.com/bowphp/framework/issues/95)
- [fix] fix [#94](https://github.com/bowphp/framework/issues/94)
- [fix] fix [#93](https://github.com/bowphp/framework/issues/93)
- [fix] fix [#92](https://github.com/bowphp/framework/issues/92)
- [fix] fix [#91](https://github.com/bowphp/framework/issues/91)
- [fix] fix [#90](https://github.com/bowphp/framework/issues/90)
- [fix] fix [#89](https://github.com/bowphp/framework/issues/89)
- [add] fix [#62](https://github.com/bowphp/framework/issues/62)
- [fix] session flash error
- [change] update Application class
- [change] update default configuration
- [change] update Event Management system
- [change] refactoring of Container Manager
- [change] use Router has base routing management

## [4.2.1] - 2020-05-28

- [fix] Refonte JWT Guard system: Force the policier singkey using the native encrypt key
- [fix] The magic method "\_\_callStatic()" must have public visibility and be static
- [add] Add the migration methods for text column type

## [4.1.2, 4.2.0] - 2020-05-23

- [add] Add "service" and "exception" command to console
- [add] Add docker composer configuration
- [change] refactoring of mail service and add unity test
- [change] Update authentication system
- [change] Update top application structure

## [4.1.1] - 2020-04-11

- [add] add jwt authentication support
- [fix] remove call static in middleware

## [4.1.0] - 2019-11-02

- [fix] validation bug fix
- [add] add seeder name and fix translate for validation stub
- [add] init routing externalisation process
- [add] add response exception
- [fix] refonte pagination system and add drop statement
- [fix] default value cost to string in Migration
- [fix] Fix count(): Parameter must be an array or an object that implements Countable in Model::find method
- [Fix] update translate in validation request stub
- [add] add swith connection in migration
- [change] update migration stub
- [fix] add exception catcher in console system
- [fix] add drop database possibity on `statement`
- [fix] [#72](https://github.com/bowphp/framework/issues/72)

## [4.0.91] - 2019-07-14

- [Fix] In `Request::class` class, the `has` method is bad
- [Fix] `route` helper call undefined method
- [Fix] In `Auth::class` class, the `check` method called `$this` with static action

### [4.0.6,4.0.5] - 2019-06-16

- Fix the unparsed parameter in url
- Fix [#60](https://github.com/bowphp/framework/issues/60)
- Fix [#61](https://github.com/bowphp/framework/issues/61)
- Fix [#58](https://github.com/bowphp/framework/issues/58)

### [4.0.3] - 2019-06-10

- Fix "Syntax error, unexpected '?', expecting variable" in Barry/Concerns/Relationship.php on line 27
- Fix "array_key_exists() expects parameter 2 to be array" in Console/Console.php on line 130
- Update application package
- Fix issue #53
