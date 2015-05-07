# Changelog

All Notable changes will be documented in this file

## 2.0.0 (IN PROGRESS)

### Added
- Refactoring of response parsing for greater flexibility.
- Added new `Disque\Connection\Manager` class to manage connections to nodes.

### Changed
- `Disque\Connection\Connection` is now named `Disque\Connection\Socket`.
- The method `setConnectionImplementation` has been moved to 
`Disque\Connection\Manager`, and renamed to `setConnectionClass`. So from
a disque instance you can change it via: 
`$disque->getConnectionManager()->setConnectionClass($class)`

## 1.0.0

### Added
- Added support for commands `HELLO`, `INFO`, `SHOW`, `ADDJOB`, `DELJOB`, 
`GETJOB`, `ACKJOB`, `FASTACK`, `ENQUEUE`, `DEQUEUE`, `QLEN`, `QPEEK`.
- Added built-in connection to Disque.
- Added support for Predis connections, and allowing adding new connection
methods via `ConnectionInterface`.
