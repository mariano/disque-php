# disque-php

[![Latest Version](https://img.shields.io/github/release/mariano/disque-php.svg?style=flat-square)](https://github.com/mariano/disque-php/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/mariano/disque-php/master.svg?style=flat-square)](https://travis-ci.org/mariano/disque-php)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/mariano/disque-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/mariano/disque-php/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/mariano/disque-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/mariano/disque-php)
[![Total Downloads](https://img.shields.io/packagist/dt/league/disque-php.svg?style=flat-square)](https://packagist.org/packages/league/disque-php)

A PHP library for the very promising [disque](https://github.com/antirez/disque)
distributed job queue.

disque-php uses the [predis](https://github.com/nrk/predis) package. The [phpredis](https://github.com/phpredis/phpredis)
PECL extension is not yet ready to run the raw commands needed by Disque.

## Installation

```bash
$ composer require mariano/disque-php --no-dev
```

If you want to run its tests remove the `--no-dev` argument.

## Usage

Start by creating an instance of `Disque\Client`, and connecting to a given
server. If no `$host` or `$port` when creating the instance, it is assumed
`127.0.0.1` and `7711` respectively:

```php
$client = \Disque\Client('127.0.0.1', 7711);
try {
    $result = $client->connect();
    var_dump($result);
} catch (\Disque\Exception\ConnectionException $e) {
    die($e->getMessage());
}
```

The above `connect()` call will return an output similar to the following:

```
[
    'version' => 1,
    'id' => "7eff078744b72d24d9ab71db1fb600c48cf7ec2f",
    'nodes' => [
        [
            'id' => "7eff078744b72d24d9ab71db1fb600c48cf7ec2f",
            'host' => "127.0.0.1",
            'port' => "7711",
            'version' => "1"
        ],
        [
            'id' => "d8f6333f5386bae67a216e0365ea09323eadc127",
            'host' => "127.0.0.1",
            'port' => "7712",
            'version' => "1"
        ],
    ]
]
```

## Testing

``` bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Support

I must reiterate this is a definite work in progress, so expect your coffee
machine to blow up when using disque-php. If you need some help or even better
want to collaborate, feel free to hit me on twitter: 
[@mgiglesias](https://twitter.com/mgiglesias)

## Security

If you discover any security related issues, please contact [@mgiglesias](https://twitter.com/mgiglesias)
instead of using the issue tracker.

## TODO

disque-php is in an ultra-development status. It can't get no more Alpha than
where it's currently at, so do not use in production. There's still a lot to
be done, and a lot of its API may suddenly change, without any prior notice
other than a commit message.

Disque commands currently supported:

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
- [ ] Pool connections based on nodes returned by `connect()`
- [ ] `QSTAT`, `SCAN` when they are implemented upstream

## Acknowledgments

First and foremost, [Salvatore Sanfilippo](https://twitter.com/antirez) for writing what looks to be the
definite solution for job queues (thanks for all the fish [Gearman](http://gearman.org/)).

Other [disque client](https://github.com/antirez/disque#client-libraries) 
libraries for the inspiration.

[The PHP League](https://thephpleague.com) for an awesome `README.md` skeleton,
and tips about packaging PHP components.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
