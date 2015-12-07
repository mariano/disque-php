# disque-php

[![Latest Version](https://img.shields.io/packagist/v/mariano/disque-php.svg?style=flat-square)](https://github.com/mariano/disque-php/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/mariano/disque-php/master.svg?style=flat-square)](https://travis-ci.org/mariano/disque-php)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/mariano/disque-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/mariano/disque-php/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/mariano/disque-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/mariano/disque-php)
[![Total Downloads](https://img.shields.io/packagist/dt/mariano/disque-php.svg?style=flat-square)](https://packagist.org/packages/mariano/disque-php)

A PHP library for the very promising [disque](https://github.com/antirez/disque)
distributed job queue. Features:

* Support for both PHP (5.5+) and HHVM
* No dependencies: Fast connection to Disque out-of-the-box
* High level API to easily push jobs to a queue, and retrieve jobs from queues
* Easily schedule jobs for execution at a certain `DateTime`
* Built in CLI tool to run jobs polled from a queue
* Use the built in `Job` class, or implement your own
* Smart node connection support based on number of jobs produced by nodes
* Supporting all current Disque commands, and allows you to easily implement custom commands
* Fully unit tested

## Installation

```bash
$ composer require mariano/disque-php --no-dev
```

If you want to run its tests remove the `--no-dev` argument.

## Usage

This library provides a [Queue API](docs/README.md#queue-api) for easy job 
pushing/pulling, and direct access to all Disque commands via its 
[Client API](docs/README.md#client-api).

Create the client:

```php
use Disque\Connection\Credentials;
use Disque\Client;

$nodes = [
    new Credentials('127.0.0.1', 7711),
    new Credentials('127.0.0.1', 7712, 'password'),
];

$disque = new Client($nodes);
```

Queue a job:

```php
$job = new \Disque\Queue\Job(['name' => 'Claudia']);
$disque->queue('my_queue')->push($job);
```

Schedule job to be processed at a certain time:

```php
$job = new \Disque\Queue\Job(['name' => 'Mariano']);
$disque->queue('my_queue')->schedule($job, new \DateTime('+2 hours'));
```

Fetch queued jobs, mark them as processed, and keep waiting on jobs:

```php
$queue = $disque->queue('my_queue');
while ($job = $queue->pull()) {
    echo "GOT JOB!";
    var_dump($job->getBody());
    $queue->processed($job);
}
```

For more information on the APIs provided, 
[read the full documentation](docs/README.md).

If you want to avoid having to build your own command line tool to run jobs that 
are pulled from your Disque queues, checkout the 
[built in CLI tool](docs/README.md#command-line-interface-cli-tool).

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

## Acknowledgments

First and foremost, [Salvatore Sanfilippo](https://twitter.com/antirez) for writing what looks to be the
definite solution for job queues (thanks for all the fish [Gearman](http://gearman.org/)).

Other [disque client](https://github.com/antirez/disque#client-libraries) 
libraries for the inspiration.

[The PHP League](https://thephpleague.com) for an awesome `README.md` skeleton,
and tips about packaging PHP components.

[Revisor](https://github.com/Revisor) for his incredible work on this library.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
