# Changelog

All Notable changes will be documented in this file. This project adheres to 
[Semantic Versioning](http://semver.org/).

## Unreleased

### Changed

- *BREAKING* `info()` now returns an associative array of information, rather
than a large string.

## [2.0.3] - 2017-02-16

### Added
- Added `nohang` option to `getJob()`. Thanks @kaecyra
- Added ability to specify job options when scheduling a job
via `schedule()`. Thanks @aleksraiden

### Changed
- Removed `predis/predis` from list of suggested packages to install

## [2.0.2] - 2016-05-10

### Fixed
- Compatibility issues with PHP 5.5, 5.6 and hhvm

## [2.0.1] - 2016-05-10

### Changed
- Job IDs should now always follow the Disque RC1 format.
- Reconnect to node when node lost connection.

### Added
- Added support for `JSCAN`
- Added support for `PAUSE`

## [2.0-alpha] - 2015-11-03

### Changed
- Exception `Disque\Connection\ResponseException` has been moved to 
`Disque\Connection\Response\ResponseException`
- The `Disque` constructor has changed, so instead of receiving an array of
IP addresses, it now receives an array of `Credentials`, where each `Credentials`
instance refers to a specific Disque node, and allows the use of passworded
nodes.
- `JobInterface` has changed to add the following methods: `getBody()`, 
`setBody()`, `getQueue()`, `setQueue()`, `getNacks()`, `setNacks()`,
`getAdditionalDeliveries()`, `setAdditionalDeliveries()`
- The `pull()` method in `Queue` no longer throws a `JobNotAvailableException`
if no job is available, but instead returns `null`.
- The `JobNotAvailableException` has been removed, as no jobs being available
is not actually an exception, but a possible acceptable outcome.
- `ManagerInterface` no longer has the `getConnectionClass()` and 
`setConnectionClass()` methods. Instead it uses the new `setConnectionFactory()`
method to allow one to specify a connection factory.
- Changed Job ID format to adapt to changes from Disque RC1

### Added
- Added `Node`, which handles the connection to a specific node.
- Added the `failed()` method to `Queue` which can be used to mark a job as
failed, therefore increasing its `NACK` counter.
- Added `ConnectionFactoryInterface`, used by `ManagerInterface`, to create
a new connection to redis.
- Added `ConnectionFactoryInterface` implementation classes `PredisFactory`
and `SocketFactory`
- Added `NodePrioritizerInterface` to allow customizing the way the client
switches through nodes based on a specific strategy.
- Added `NodePrioritizerInterface` implementation classes 
`ConservativeJobCountPrioritizer`, `RandomPrioritizer` and `NullPrioritizer`
- Added option `withcounters` to the `$options` argument in `getJob()` which
allows the returned job to include its `NACK` and additional deliveries 
counters.
- Added support for `NACK`

## [1.3.0] - 2015-05-18

### Added
- Added support for `WORKING`.
- Added `processing()` method to Queue API.
- Added `$password` option to `addServer()` in `Disque\Client`.

### Changed
- By default when creating a new `Disque\Client` without arguments NO server
is pre-loaded. You will have to manually add servers via `addServer()`, or
specify them to the `Disque\Client` constructor.

## [1.2.1] - 2015-05-14

### Changed
- `QPEEK` changed in upstream and now returns the job queue. Client API has
been modified to reflect this.
- `CommandInterface` has a new method: `isBlocking()`, which tells if the
given command should block while waiting for a response to not be affected
by timeouts.

### Added
- Added support for `QSCAN`.

### Fixed
- Fixed bug where if the connection would timeout while waiting for a response
a `ConnectionException` would be thrown. This affected `getJob()` which should
not be interrupted by a timeout. This required a change in the definition
of `CommandInterface` by adding the method `isBlocking()`

## [1.2.0] - 2015-05-12

### Changed
- `JobInterface` is now a simpler interface. Its `load()` and `dump()` methods 
have been moved to a `MarshalInterface`, effectively changing how custom Job 
classes work.
- `Disque\Queue\MarshalException` has been moved to 
`Disque\Queue\Marshal\MarshalException`.
- The `setJobClass()` method in `Queue` has been removed. Instead use 
`setMarshaler()`, which should be given an instance of
`Disque\Queue\Marshaler\MarshalerInterface`.

## [1.1.0] - 2015-05-10

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
is an instance of `Disque\Queue\Queue`.
- Added `schedule()` method to `Disque\Queue` that allows to easily schedule
jobs to be processed at a certain time.

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
- Fixed issue where if no options were provided to `addJob()`, yet all three
parameters were specified, an `InvalidCommandArgumentException` was thrown.

## [1.0.0] - 2015-05-04

### Added
- Added support for commands `HELLO`, `INFO`, `SHOW`, `ADDJOB`, `DELJOB`, 
`GETJOB`, `ACKJOB`, `FASTACK`, `ENQUEUE`, `DEQUEUE`, `QLEN`, `QPEEK`.
- Added built-in connection to Disque.
- Added support for Predis connections, and allowing adding new connection
methods via `ConnectionInterface`.

[2.0.3]: https://github.com/mariano/disque-php/releases/tag/2.0.3
[2.0.2]: https://github.com/mariano/disque-php/releases/tag/2.0.2
[2.0.1]: https://github.com/mariano/disque-php/releases/tag/2.0.1
[2.0-alpha]: https://github.com/mariano/disque-php/releases/tag/2.0-alpha
[1.3.0]: https://github.com/mariano/disque-php/releases/tag/1.3.0
[1.2.1]: https://github.com/mariano/disque-php/releases/tag/1.2.1
[1.2.0]: https://github.com/mariano/disque-php/releases/tag/1.2.0
[1.1.0]: https://github.com/mariano/disque-php/releases/tag/1.1.0
[1.0.0]: https://github.com/mariano/disque-php/releases/tag/1.0.0
