# BowPHP Framework Roadmap

> Living document based on source code analysis (5.x branch) and the project manifesto.
> Last updated: May 2026

---

## Current Framework State

### Existing Modules (`/src` Analysis)

| Module                 | Status   | Description                                    |
| ---------------------- | -------- | ---------------------------------------------- |
| **Application**        | ✅ Stable | Bootstrap, exception handling, kernel          |
| **Auth**               | ✅ Stable | Guards (Session, JWT), Authentication          |
| **Cache**              | ✅ Stable | Adapters: Database, Filesystem, Redis          |
| **Configuration**      | ✅ Stable | Loader, Env, Logger configuration              |
| **Console**            | ✅ Stable | 26 commands, generators, stubs                 |
| **Container**          | ✅ Stable | DI container, middleware dispatcher            |
| **Database/Barry ORM** | ✅ Stable | MySQL, PostgreSQL, SQLite + Relations          |
| **Event**              | ✅ Stable | Event dispatcher, listeners, queue integration |
| **Http**               | ✅ Stable | Request, Response, Client, Exceptions          |
| **Mail**               | ✅ Stable | SMTP, Native adapters, queue support           |
| **Messaging**          | ✅ Stable | SMS, Mail, Slack, Telegram, Database channels  |
| **Middleware**         | ✅ Stable | Auth, CSRF, Base middleware                    |
| **Queue**              | ✅ Stable | Beanstalkd, Database, SQS, Sync adapters       |
| **Router**             | ✅ Stable | REST methods, prefixes, middlewares, resources |
| **Security**           | ✅ Stable | Crypto, Hash, Sanitize, Tokenize               |
| **Session**            | ✅ Stable | Cookie, File, Database, Redis adapters         |
| **Storage**            | ✅ Stable | Disk, FTP, S3 services                         |
| **Support**            | ✅ Stable | Helpers, Collection, Str, Log, Env             |
| **Testing**            | ✅ Stable | TestCase, Assertions, KernelTesting            |
| **Translate**          | ✅ Stable | i18n support                                   |
| **Validation**         | ✅ Stable | Validation rules, custom messages              |
| **View**               | ✅ Stable | Tintin (default), Twig support                 |

### Current Dependencies

**Required:**

* PHP ^8.1
* bowphp/tintin ^3.0 (template engine)
* filp/whoops ^2.1 (error handling)
* nesbot/carbon 3.8.4 (dates)
* fakerphp/faker ^1.20 (testing data)
* ramsey/uuid ^4.7 (UUIDs)

**Dev/Suggested:**

* pda/pheanstalk ^5.0 (Beanstalkd)
* aws/aws-sdk-php ^3.87 (S3)
* bowphp/policier ^3.0 (JWT)
* predis/predis ^2.1 (Redis)
* twilio/sdk ^8.3 (SMS)
* bowphp/slack-webhook ^1.0 (Slack)

---

## ✅ Recently Delivered (Spring 2026)

Highlights from the latest iterations — already merged into `5.x`. Full details are available in the CHANGELOG.

### Routing

* PHP 8 attribute routing support (see dedicated section below).
* `Router::$routes` converted to instance state (fixes shared state leaks between tests).
* `#[Controller]` name prefix applied to child routes; inherited methods ignored during scanning.

### Barry ORM

* `SoftDelete` trait (`delete` → `deleted_at`, `restore`, `forceDelete`, `withTrashed` / `onlyTrashed` / `withoutTrashed`, events `model.restoring/restored/forceDeleting/forceDeleted`).
* Fixed `array` cast: no longer returns `stdClass`.
* Removed dead `$soft_delete` property (replaced by the trait).
* `EventTrait::fireEvent` / `formatEventName` visibility expanded to `protected` for child traits.

### Validation

* New rules: `url`, `ip` (+ `ip:v4`, `ip:v6`), `boolean`, `json`, `uuid`, `confirmed`, `different:field`, `between:min,max`.
* Fixed priority handling: `nullable|required` now allows `required` to execute properly (and the inner-loop break now uses the correct variable).

### Testing Infrastructure

* `TestCase` refactored: real DELETE/PATCH support (no more `_method` hack), `head()` / `options()`, shared logic through `newHttpClient()`, automatic attachment reset, default port 8080.
* `Env::reset()` added for cleaner test isolation.
* `SchedulerCommand` now automatically loads `routes/scheduler.php` and tolerates a missing Loader.
* `addEnum` / `changeEnum`: explicit error messages (mention the `size` key).
* Test bootstrap now filters `E_DEPRECATED` coming from `vendor/` (`lcobucci/jwt v3.2.5`, spatie 4.x).
* Pagination: tests were calling `total()` instead of `totalPages()` — 24 test cases fixed.

### Tintin (vendored)

* Atomic cache (`rename`), recursive `mkdir`, invalidation based on `filemtime` (instead of `fileatime`).
* `Compiler::compile` no longer removes empty lines; added `?>\n\n` post-pass to preserve indentation in `<pre>/<code>` snippets.
* `Tintin::renderString` now uses `tempnam()` + `try/finally`; removed destructive `trim()`.
* Tightened `{{ ... }}` escaping heuristic while remaining compatible with Vue/Angular.
* Extended `directivesProtected` (`csrf`, `macro/endmacro`, `lang`, `flash`, `notempty`, etc.).

### Documentation & READMEs

* Full audit of `docs/docs/*.mdx` (ORM, Router, Validation, Migration, Mail, Storage, Messaging, Container, Pagination, Scheduler, Task, Testing, Configuration, Concept, Controller, CQRS, Database, Policier, Service, Session, SoAuth, Structure, Upload, View, Package, Contribution).
* Updated README (badges, test counters, soft delete, attribute routing, command helpers).
* `microservice` (subproject): `MicroserviceConfiguration` refactor (`extends Configuration`, clean PSR-4), Bow-integrated `microservice.php`, fixed `Bow\Console\Command\Generator` namespace.

---

## 🔴 NOW — 0 to 3 Months (Stabilization & Consolidation)

### Testing and CI/CD

| Task                                          | Status     | Priority | Notes                                                                                                                     |
| --------------------------------------------- | ---------- | -------- | ------------------------------------------------------------------------------------------------------------------------- |
| Separate unit tests from integration tests    | ⏳ Planned  | High     | DB/FTP/S3 tests require external services                                                                                 |
| Add PHPUnit `@group` annotations              | ⏳ Planned  | High     | `@group unit`, `@group integration`, `@group database`                                                                    |
| Configure GitHub Actions with Docker services | ⏳ Planned  | High     | MySQL, PostgreSQL, Redis for CI                                                                                           |
| Increase unit test coverage                   | 🔄 Ongoing | Medium   | 1,600+ tests, 0 logical failures. Recent additions: SoftDelete, AttributeRouteRegistrar, new validation rules, Pagination |
| Integrate PHPStan level 5+                    | ⏳ Planned  | Medium   | Current constraint: `phpstan/phpstan: ^0.12.87` — upgrade to ^1.x before targeting higher levels                          |

### Code Fixes

| Task                                                             | Status | Priority | Notes                                                            |
| ---------------------------------------------------------------- | ------ | -------- | ---------------------------------------------------------------- |
| Fix middleware attribute test (shared state between tests)       | ✅ Done | -        | `Router::$routes` converted to instance state                    |
| Fix Pagination tests calling `total()` instead of `totalPages()` | ✅ Done | -        | 24 tests fixed                                                   |
| Fix Barry model `array` cast returning `stdClass`                | ✅ Done | -        | `Model::executeDataCasting` + `parseToJson($value, assoc: true)` |
| Fix Validator `nullable\|required` priority                      | ✅ Done | -        | `nullable` no longer short-circuits `required`                   |
| Fix `EnvTest` singleton pollution between tests                  | ✅ Done | -        | `Env::reset()` added                                             |
| Fix `SchedulerCommand` (`routes/scheduler.php` loading)          | ✅ Done | -        | `loadSchedulerFile()` updated, tolerates missing Loader          |
| Remove dead `Model::$soft_delete` property                       | ✅ Done | -        | Replaced with a fully functional trait                           |
| Improve `addEnum` / `changeEnum` error messages                  | ✅ Done | -        | Explicitly mention the `size` key                                |
| Standardize method signatures                                    | ✅ Done | -        | PHP 8.1+ nullable types                                          |
| Fix `(double)` → `(float)` cast                                  | ✅ Done | -        | `Model.php`                                                      |
| Handle `array_key_exists` with null key                          | ✅ Done | -        | `Console.php`                                                    |
| Create test directory if missing                                 | ✅ Done | -        | `CustomCommand.php`                                              |

### Documentation

| Task                                  | Status    | Priority | Notes                                                                                                                                       |
| ------------------------------------- | --------- | -------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| Update README with API-first examples | ✅ Done    | -        | Test counters, corrected examples (`User::retrieve`, `persist()`, `$app`), attribute routing and soft delete highlighted                    |
| Document required configurations      | ✅ Done    | -        | Full audit of `docs/docs/*.mdx` (ORM, Router, Validation, Migration, Storage, Mail, Notifier, Container, Pagination, Scheduler, Task, etc.) |
| Create a detailed contribution guide  | ⏳ Planned | Low      | Beyond the current `CONTRIBUTING.md`                                                                                                        |

---

## 🟠 NEXT — 3 to 6 Months (New Features)

### Queue - Redis Adapter

| Task                                   | Status    | Priority | Notes                                       |
| -------------------------------------- | --------- | -------- | ------------------------------------------- |
| Create `RedisAdapter` for Queue        | ⏳ Planned | High     | `predis/predis` already in dev dependencies |
| Implement delayed jobs with Redis ZADD | ⏳ Planned | High     |                                             |
| Add queue monitoring through CLI       | ⏳ Planned | Medium   | `bow queue:status`                          |

### Router - PHP 8 Attributes ✅ Delivered

| Task                                                 | Status | Priority | Notes                                                                                  |
| ---------------------------------------------------- | ------ | -------- | -------------------------------------------------------------------------------------- |
| Create namespace `Bow\Router\Attributes`             | ✅ Done | -        | `src/Router/Attributes/`                                                               |
| Implement `#[Controller]`                            | ✅ Done | -        | `prefix`, `middleware`, `name` (route name prefix)                                     |
| Implement `#[Get]`, `#[Post]`, `#[Put]`, `#[Delete]` | ✅ Done | -        | + `#[Patch]`, `#[Options]`, `#[Route]` (multi-verb), all repeatable                    |
| Add `$app->register(Controller::class)`              | ✅ Done | -        | Also accepts an array of controllers                                                   |
| `AttributeRouteRegistrar`                            | ✅ Done | -        | Refactored: name prefix applied, inherited methods ignored, attribute subclass support |
| Tests + stubs                                        | ✅ Done | -        | `tests/Routing/AttributeRouteIntegrationTest.php`                                      |

### Cache - Memcached Adapter

| Task                                      | Status    | Priority | Notes |
| ----------------------------------------- | --------- | -------- | ----- |
| Create `MemcachedAdapter`                 | ⏳ Planned | Medium   |       |
| Improve Redis resiliency (auto-reconnect) | ⏳ Planned | Medium   |       |

### Messaging - Push Notifications

| Task                                  | Status    | Priority | Notes          |
| ------------------------------------- | --------- | -------- | -------------- |
| Create `FcmChannelAdapter` (Firebase) | ⏳ Planned | Medium   |                |
| Create `ApnsChannelAdapter` (Apple)   | ⏳ Planned | Medium   |                |
| Improve `TelegramChannelAdapter`      | ⏳ Planned | Low      | Already exists |
| Improve `SlackChannelAdapter`         | ⏳ Planned | Low      | Already exists |

### Database

| Task                                      | Status    | Priority | Notes                          |
| ----------------------------------------- | --------- | -------- | ------------------------------ |
| Add SQL Server support                    | ⏳ Planned | Medium   |                                |
| Create Array/FileWriter adapter for tests | ⏳ Planned | Medium   | Removes DB dependency in tests |

---

## 🟢 LATER — 6 to 12 Months (Long-Term Vision)

### Performance and Modernization

| Task                                         | Status    | Priority | Notes                  |
| -------------------------------------------- | --------- | -------- | ---------------------- |
| Swoole/FrankenPHP support                    | ⏳ Planned | Medium   | Non-blocking servers   |
| Official Docker images                       | ⏳ Planned | Medium   | Production-optimized   |
| Serverless support (Lambda, Cloud Functions) | ⏳ Planned | Low      | Dedicated HTTP handler |

### Ecosystem

| Task                                             | Status    | Priority | Notes                |
| ------------------------------------------------ | --------- | -------- | -------------------- |
| Package `bowphp/payment`                         | ⏳ Planned | High     | African mobile money |
| Package `bowphp/logviewer` or `bowphp/telescope` | ⏳ Planned | Medium   | Observability        |
| Adapt `laravel-notify` for Bow                   | ⏳ Planned | Low      | Notification UI      |

### Observability

| Task                           | Status    | Priority | Notes                           |
| ------------------------------ | --------- | -------- | ------------------------------- |
| Optional OpenTelemetry module  | ⏳ Planned | Medium   | Request, job, and query tracing |
| Prometheus/Grafana integration | ⏳ Planned | Low      | Production metrics              |

---

## Legend

* ✅ **Done**: Completed task
* ⏳ **Planned**: Scheduled task
* 🔄 **Ongoing**: Work in progress
* ❌ **Cancelled**: Abandoned task

---

## How to Contribute

1. Pick a task from the **NOW** section (high priority)
2. Open an issue to discuss the implementation
3. Create a branch named `feature/task-name`
4. Follow project conventions (see `CONTRIBUTING.md`)
5. Submit a PR with tests

---

## Important Notes

### About Testing

Current failures during `composer test` are mainly caused by:

1. **Unavailable external services** (not framework bugs):

   * MySQL: Connection refused / Access denied
   * PostgreSQL: Connection refused
   * FTP: Connection refused
   * S3: Invalid endpoint
   * Beanstalkd: Connection refused

2. **SQLite test isolation issues**: Some tests share database state, causing intermittent failures.

**Recommended solution**: Split tests into groups (`@group unit`, `@group integration`) and configure CI with Docker Compose for integration tests.

### Project Philosophy

Every contribution must respect the manifesto:

* **Simplicity** > Sophistication
* **Readability** > Extreme conciseness
* **API-first**: JSON backends are the priority
* **Performance**: Minimal bootstrap, fast response times
* **Control**: Developers retain full control over their architecture
