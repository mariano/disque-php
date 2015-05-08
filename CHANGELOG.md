# Changelog

All Notable changes will be documented in this file. This project adheres to 
[Semantic Versioning](http://semver.org/).

## [1.1.0]

### Added
- Refactoring of response parsing for greater flexibility.
- Added new `Disque\Connection\Manager` class to manage connections to nodes.
- `GETJOB` can now influence which node we are connected to. By means of
`$disque->getConnectionManager()->setMinimumJobsToChangeNode()` we can specify
that if a certain node produces that many jobs, then we should instead connect
to the node producing those jobs (as suggested by [Disque](https://github.com/antirez/disque#client-libraries)
itself).
- Added `Disque\Queue\Queue` and `Disque\Queue\Job` class to offer a higher
level API that simplifies queueing and fetching jobs
- Added method `queue()` to `Disque\Client` to create / fetch a queue which
is an instance of `Disque\Queue\Queue`

### Changed
- `Disque\Connection\Connection` is now named `Disque\Connection\Socket`.
- The method `setConnectionImplementation` has been moved to 
`Disque\Connection\Manager`, and renamed to `setConnectionClass`. So from
a disque instance you can change it via: 
`$disque->getConnectionManager()->setConnectionClass($class)`
- Moved exceptions around:
    * `Disque\Exception\InvalidCommandArgumentException` to 
    `Disque\Command\Argument\InvalidArgumentException`
    * `Disque\Exception\InvalidCommandException` to 
    `Disque\Command\InvalidCommandException`
    * `Disque\Exception\InvalidCommandOptionException`
    to `Disque\Command\Argument\InvalidOptionException`
    * `Disque\Exception\InvalidCommandResponseException`
    to `Disque\Command\Response\InvalidResponseException`
    * `Disque\Exception\Connection\Exception\ConnectionException`
    to `Disque\Connection\ConnectionException`
    * `Disque\Exception\Connection\Exception\ResponseException`
    to `Disque\Connection\ResponseException`
    * `Disque\Exception\DisqueException`
    to `Disque\DisqueException`

### Fixed
- Fixed issue where when a `timeout` was using when calling `getJob()`, and the
call timed out because there were no jobs available, an non-array would be
returned. No on that case it will return an empty array.

## [1.0.0] - 2015-05-04

### Added
- Added support for commands `HELLO`, `INFO`, `SHOW`, `ADDJOB`, `DELJOB`, 
`GETJOB`, `ACKJOB`, `FASTACK`, `ENQUEUE`, `DEQUEUE`, `QLEN`, `QPEEK`.
- Added built-in connection to Disque.
- Added support for Predis connections, and allowing adding new connection
methods via `ConnectionInterface`.

[1.1.0]: https://github.com/mariano/disque-php/compare/1.0.0...HEAD
[1.0.0]: https://github.com/mariano/disque-php/releases/tag/1.0.0
