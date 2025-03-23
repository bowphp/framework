# Bow Framework

<a href="https://github.com/bowphp/docs" title="docs"><img src="https://img.shields.io/badge/docs-read%20docs-blue.svg?style=flat-square"/></a>
<a href="https://packagist.org/packages/bowphp/framework" title="version"><img src="https://img.shields.io/packagist/v/bowphp/framework.svg?style=flat-square"/></a>
<a href="https://github.com/bowphp/framework/blob/master/LICENSE" title="license"><img src="https://img.shields.io/github/license/mashape/apistatus.svg?style=flat-square"/></a>

To use this package, please create an application from this package [bowphp/app](https://github.com/bowphp/app)

## The Framework Main Feature

- Full-featured database classes with support for several platforms.
- Query Builder Database Support
- Form and Data Validation
- Security and XSS Filtering
- Data Encryption
- Session Management
- Controller Revolver
- Middleware Support
- Small and Robust Routing
- File Uploading Class
- Pagination
- CQRS helpful implementation
- File System Management with many drivers like S3 and FTP (Support connection switch)
- Extensible with an external package that can plug in
- Application logs Management
- Database Connection (MySQL, SQLite, PostgreSQL)
- Simplest ORM which is named Barry
- Cache support (Filesystem, Redis, Database caching)
- Event Management (Interpage Event)
- Emailing (SMTP, SES, Native PHP mail supports)
- Task runner (Which helps you to generate the controller and match more)
- Unit Testing Support
- View Rendering with [bowphp/tintin](https://github.com/bowphp/tintin) package (Tintin is the very small php template)
- Very easy Translate Management
- Many helpers
- The native authentication system
- Producer/Consumer with beanstalkd, database, Redis, SQS backend

## Project Structure

The project is organized into the following directories, each representing an independent module:

- **src/**: Source code for the Bow Framework.
  - **Application/**: Main application logic and configuration.
  - **Auth/**: Authentication and authorization management.
  - **Cache/**: Caching mechanisms.
  - **Configuration/**: Configuration settings management.
  - **Console/**: Console commands and utilities.
  - **Container/**: Dependency injection and service container.
  - **Contracts/**: Interfaces and contracts for various components.
  - **Database/**: Database connections and ORM.
  - **Event/**: Event management and dispatching.
  - **Http/**: HTTP requests and responses management.
  - **Mail/**: Email sending and configuration.
  - **Messaging/**: Messaging and notifications.
  - **Middleware/**: Middleware classes for request handling.
  - **Queue/**: Job queues and background processing.
  - **Router/**: HTTP request routing.
  - **Security/**: Security features like encryption and hashing.
  - **Session/**: User session management.
  - **Storage/**: File storage and retrieval.
  - **Support/**: Utility classes and helper functions.
  - **Testing/**: Unit testing classes and utilities.
  - **Translate/**: Translation and localization.
  - **Validation/**: Data validation.
  - **View/**: View rendering and templating.
- **tests/**: Unit tests for the project.

## Contributing

Thank you for considering contributing to Bow Framework! The contribution guide is in the framework documentation.

- [Franck DAKIA](https://github.com/papac)
- [Thank's collaborators](https://github.com/bowphp/framework/graphs/contributors)

### Contribution Guidelines

We welcome contributions from the community! To contribute to the project, please follow these steps:

1. Fork the project and clone it to your local machine.
2. Create a new branch for your changes.
3. Make your changes and commit them.
4. Push your changes to your fork and create a pull request.

For more detailed information, refer to the `CONTRIBUTING.md` file.

## Contact

[papac@bowphp.com](mailto:papac@bowphp.com) - [@papacdev](https://twitter.com/papacdev)

Please, if there is a bug on the project contact me by email or leave me a message on [Slack](https://bowphp.slack.com).
or [join us on Slask](https://join.slack.com/t/bowphp/shared_invite/enQtNzMxOTQ0MTM2ODM5LTQ3MWQ3Mzc1NDFiNDYxMTAyNzBkNDJlMTgwNDJjM2QyMzA2YTk4NDYyN2NiMzM0YTZmNjU1YjBhNmJjZThiM2Q)

