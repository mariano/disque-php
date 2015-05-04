# disque-php

[![Latest Version](https://poser.pugx.org/mariano/disque-php/v/stable)](https://packagist.org/packages/s12v/phpque)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/mariano/disque-php/master.svg?style=flat-square)](https://travis-ci.org/mariano/disque-php)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/mariano/disque-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/mariano/disque-php/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/mariano/disque-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/mariano/disque-php)
[![Total Downloads](https://img.shields.io/packagist/dt/mariano/disque-php.svg?style=flat-square)](https://packagist.org/packages/mariano/disque-php)

A PHP library for the very promising [disque](https://github.com/antirez/disque)
distributed job queue. Features:

* Support for both PHP (5.5+) and HHVM
* No dependencies: Fast connection to Disque out-of-the-box
* Support for multi-node connection
* Connect to Disque with the built-in connection, or reutilize your existing Redis client (such as [predis](https://github.com/nrk/predis))
* Supporting all current Disque commands, and allows you to easily implement support for custom commands
* Fully unit tested

## Installation

```bash
$ composer require mariano/disque-php --no-dev
```

If you want to run its tests remove the `--no-dev` argument.

## Usage

Connect:

```php
$client = \Disque\Client([
    '127.0.0.1:7111',
    '127.0.0.2:7112'
]);
try {
    $client->connect();
} catch (\Disque\Exception\ConnectionException $e) {
    die($e->getMessage());
}
```

Queue jobs:

```php
$payload = ['name' => 'Mariano'];
$client->addJob('queue', json_encode($payload));
```

Get jobs from queue:

```php
$job = $client->getJob('queue');
$payload = json_decode($job, true);
var_dump($payload);
```

For a full coverage of the API, read the [full documentation](docs/README.md).

## Testing

``` bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Support

If you need some help or even better want to collaborate, feel free to hit me 
on twitter: [@mgiglesias](https://twitter.com/mgiglesias)

## Security

If you discover any security related issues, please contact [@mgiglesias](https://twitter.com/mgiglesias)
instead of using the issue tracker.

## TODO

- [x] HELLO
- [x] INFO
- [x] SHOW
- [x] ADDJOB
- [x] DELJOB
- [x] GETJOB
- [x] ACKJOB
- [x] FASTACK
- [x] ENQUEUE
- [x] DEQUEUE
- [x] QLEN
- [x] QPEEK
- [ ] `QSTAT`, `SCAN` when they are implemented upstream
- [x] Add support for several connections
- [ ] Allow GETJOB to influence what node the Client should be connected to
- [x] Implement direct protocol to Disque to avoid depending on Predis
- [x] Turn Predis integration into a ConnectionInterface
- [x] Allow user to specify their own ConnectionInterface implementation
- [ ] Add support for PSR Logging

## Acknowledgments

First and foremost, [Salvatore Sanfilippo](https://twitter.com/antirez) for writing what looks to be the
definite solution for job queues (thanks for all the fish [Gearman](http://gearman.org/)).

Other [disque client](https://github.com/antirez/disque#client-libraries) 
libraries for the inspiration.

[The PHP League](https://thephpleague.com) for an awesome `README.md` skeleton,
and tips about packaging PHP components.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
