# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
