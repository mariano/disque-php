# Changelog

All Notable changes will be documented in this file

## 1.0.1 (IN PROGRESS)

### Added
- Refactoring of response parsing for greater flexibility

## 1.0.0

### Added
- Added support for commands `HELLO`, `INFO`, `SHOW`, `ADDJOB`, `DELJOB`, 
`GETJOB`, `ACKJOB`, `FASTACK`, `ENQUEUE`, `DEQUEUE`, `QLEN`, `QPEEK`
- Added built-in connection to Disque
- Added support for Predis connections, and allowing adding new connection
methods via `ConnectionInterface`
