# disque-php

## Installation

Install disque-php via Composer:

```bash
$ composer require mariano/disque-php --no-dev
```

If you want to run its tests remove the `--no-dev` argument.

## Connecting

First you will need to create an instance of `Disque\Client`, specifying a list
of hosts and ports where different Disque nodes are installed:

```php
$client = new Disque\Client([
    '127.0.0.1:7711',
    '127.0.0.1:7712'
]);
```

At this point no connection is yet established. You force the connection via 
the `connect()` method. As recommended by Disque, the connection is done 
as follows:

* The list of servers (specified via the constructor, or via the 
`addServer($host, $port)` method) is used to pick a random server.
* A connection is attempted against the picked server. If it fails, another
random node is tried.
* If a connection is successfull, the `HELLO` command is issued against this
server. If this fails, another random node is tried.
* If no connection is established and there are no servers left, a
`Disque\Connection\Exception\ConnectionException` is thrown.

Example call:

```php
$result = $client->connect();
var_dump($result);
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

### Using another connector

By default disque-php does not require any other packages or libraries. It has
its own connector to Disque, that is fast and focused. If you wish to instead
use another connector to handle the connection with Disque, you can specify
so via the `setConnectionImplementation()` method. For example, if you wish
to use [predis](https://github.com/nrk/predis) (maybe because you are already
using its PHP extension), you would first add predis to your Composer
requirements:

```bash
$ composer require predis/predis --no-dev
```

And then configure the connection implementation class:

```php
$client->setConnectionImplementation(\Disque\Connection\Predis::class);
```
