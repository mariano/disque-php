# disque-php

A PHP library for the very promising [disque](https://github.com/antirez/disque)
distributed job queue.

disque-php uses the [redis](https://pecl.php.net/package/redis) PECL extension,
also known as [phpredis](https://github.com/phpredis/phpredis).

## Installation

```bash
$ composer require mariano/disque-php --no-dev
```

If you want to run its tests remove the `--no-dev` argument.

## What's included

disque-php is in an ultra-development status. It can't get no more Alpha than
where it's currently at, so do not use in production. There's still a lot to
be done, and a lot of its API may suddenly change, without any prior notice
other than a commit message.

Disque commands currently supported:

- [x] HELLO
- [x] INFO
- [ ] SHOW
- [ ] ADDJOB
- [ ] DELJOB
- [ ] GETJOB
- [ ] ACKJOB
- [ ] FASTACK
- [ ] ENQUEUE
- [ ] DEQUEUE
- [ ] QLEN
- [ ] QPEEK

## License

This code is released under the MIT license, see `LICENSE` file for more
details.

## Support

I must reiterate this is a definite work in progress, so expect your coffee
machine to blow up when using disque-php. If you need some help or even better
want to collaborate, feel free to hit me on twitter: 
[@mgiglesias](https://twitter.com/mgiglesias)

## Acknowledgments

First and foremost, [Salvatore Sanfilippo](https://twitter.com/antirez) for writing what looks to be the
definite solution for job queues (thanks for all the fish [Gearman](http://gearman.org/)).

Other [disque client](https://github.com/antirez/disque#client-libraries) 
libraries for the inspiration.
