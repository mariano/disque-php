# Installation

Install disque-php via Composer:

```bash
$ composer require mariano/disque-php --no-dev
```

If you want to run its tests remove the `--no-dev` argument.

# Creating the client

First you will need to create an instance of `Disque\Client`, specifying a list
of hosts and ports where different Disque nodes are installed:

```php
$client = new \Disque\Client([
    '127.0.0.1:7711',
    '127.0.0.1:7712'
]);
```

If no host is specified, `127.0.0.1:7711` is assumed. Hosts can also be added
via the `addServer($host, $port)` method:

```php
$client = new \Disque\Client();
$client->addServer('127.0.0.1', 7712);
```

Now you are ready to start interacting with Disque. This library provides a 
**Queue API** for easy job pushing/pulling, and direct 
access to all Disque commands via its **Client API**. If you are
looking to get jobs into queues, and process them in a simple way, then the 
[Queue API](#queue-api) is your best choice as it abstracts Disque's internal
protocol. If you instead wish to do more advanced stuff with Disque, use the 
[Client API](#client-api).

# Queue API

To simplify the most common tasks with Disque this package offers a higher level
API to push jobs to Disque, and retrieve jobs from it.

When using the Queue API you won't need to tell the client when to connect, 
as it will do it automatically as needed. If however you want to influence the 
way connections are established (such as by setting it to smartly switch 
between nodes based on where the jobs come from), then go ahead and 
[read the documentation on connections](#connecting) from the Client API.

## Getting a queue

Before being able to push jobs into queues, or pull jobs from them, you will
need to get a queue. You can fetch a queue by name. No need to create nor delete 
queues, Disque manages the queue lifecycle automatically.

```php
$queue = $disque->queue('emails');
```

Once you have obtained the queue, you can either push jobs to it, or pull jobs
from it. Jobs are instances of `Disque\Queue\JobInterface`, which offers the
following methods (among others used by the Queue API):

* `getBody(): mixed`: gets the body of the job.
* `setBody(mixed $body)`: sets the body of the job.

By default the Queue API uses an implementation (`Disque\Queue\Job`) that 
forces job bodies to be arrays, and serializes the body to JSON when sending it
to Disque. If you want to change the default job implementation used in a queue,
you can do so as shown in the [Changing the Job class](#changing-the-job-class) 
section.

## Pushing jobs to the queue

The simplest way to push a job to the queue is by using its `push()` method:

```php
$job = new \Disque\Queue\Job(['name' => 'Mariano']);
$disque->queue('my_queue')->push($job);
```

You can specify different options that will affect how the job is placed on
the queue through `push()` second, optional, argument `$options`. For available
options see the documentation on [addJob](#addjob). For example to push a job 
to the queue but automatically remove it from the queue if after 1 minute it 
wasn't processed, we would do:

```php
$job = new \Disque\Queue\Job(['description' => 'To be handled within the minute!']);
$disque->queue('my_queue')->push($job, ['ttl' => 60]);
```

If you want to push a job to be processed at a specific time in the future,
check out the [Scheduling jobs](#scheduling-jobs) section.

## Pulling jobs from the queue

You get jobs, one at a time, using the `pull()` method. If there are no jobs
available, this call **will block** until a job is placed on the queue. To get 
a job:

```php
$queue = $disque->queue('my_queue');
$job = $queue->pull();
var_dump($job->getBody());
$queue->processed($job);
```

Make sure to always acknowledge a job once you are done processing it, as
explained in the [Acknowledging jobs](#acknowledging-jobs) section.

You can obviously process as many jobs as there are already, or become 
available:

```php
$queue = $disque->queue('my_queue');
while ($job = $queue->pull()) {
    echo "GOT JOB!\n";
    var_dump($job->getBody());
    $queue->processed($job);
}
```

The call to `pull()` is blocking, so you may find yourself in the need to do 
something with the time you spend while waiting for jobs. Fortunately `pull()` 
receives an optional argument: the number of milliseconds to wait for a job. 
If this time passed and no job was available, a 
`Disque\Queue\JobNotAvailableException` is thrown. For example if we want to
wait for jobs, but do something else if after 1 second passed without jobs,
and then keep waiting for jobs, we would do:

```php
$queue = $disque->queue('my_queue');
while (true) {
    try {
        $job = $queue->pull(1000);
    } catch (\Disque\Queue\JobNotAvailableException $e) {
        // Do something else while waiting!
        echo "Still waiting...\n";
        continue;
    }

    echo "GOT JOB!\n";
    var_dump($job->getBody());
    $queue->processed($job);
}
```

## Scheduling jobs

If you want to push jobs to the queue but don't have them ready for processing
until a certain time, you can take advantage of the `schedule()` method. This
method takes the job as its first argument, and a `DateTime` (which should be
set to the future) for when it should be ready. For example to push a job and 
have it ready for processing in 15 seconds, we would do:

```php
$job = new \Disque\Queue\Job(['name' => 'Mariano']);
$disque->queue('my_queue')->schedule($job, new \DateTime('+15 seconds'));
```

While testing this feature, you can wait for jobs every second to see it working
as expected:

```php
while (true) {
    try {
        $job = $disque->queue('my_queue')->pull(1000);
    } catch (\Disque\Queue\JobNotAvailableException $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Waiting...\n";
        continue;
    }

    echo "[" . date('Y-m-d H:i:s') . "] GOT JOB!";
    var_dump($job->getBody());
}
```

## Acknowledging jobs

Once you have processed a job succesfully, you will need to acknowledge it, to
avoid Disque from putting it back on the queue 
([details from Disque itself](https://github.com/antirez/disque#give-me-the-details)).
You do so via the `processed()` method, like so:

```php
$disque->queue('my_queue')->processed($job);
```

## Changing the Job class

If you want to change the class used to represent a job, you can specify the 
new class implementation (which should implement `Disque\Queue\JobInterface`)
via the queue's `setJobClass()` method. If you are comfortable with the job 
body being an array, and serialization on Disque being in JSON format, then you 
can easily extend the default job implementation, and add your logic on top of 
it:

```php
class EmailJob extends \Disque\Queue\Job implements \Disque\Queue\JobInterface
{
    private $email;
    private $subject;
    private $message;

    public static function getInstance($email, $subject, $message)
    {
        $job = new static();
        $job->setBody(compact('email', 'subject', 'message'));
        return $job;
    }

    public function getBody()
    {
        return [
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message
        ];
    }

    public function setBody($body)
    {
        if (!is_array($body)) {
            throw new InvalidArgumentException("This doesn't look like an email!");
        }

        $body += array_fill_keys(['email', 'subject', 'message'], null);
        $this->email = $body['email'];
        $this->subject = $body['subject'];
        $this->message = $body['message'];
    }

    public function send()
    {
        echo "SEND EMAIL TO {$this->email}:\n";
        echo $this->subject . "\n\n";
        echo $this->message;
    }
}
```

You can now push your email jobs to a queue:

```php
$queue = $disque->queue('emails');
$queue->setJobClass(EmailJob::class);

$job = EmailJob::getInstance('john@example.com', 'Hello world!', 'Hello from Disque :)');
$queue->push($job);
```

When pulling jobs from the queue, you can take advantage of your custom job
implementation:

```php
$queue = $disque->queue('emails');
$queue->setJobClass(EmailJob::class);

while ($job = $queue->pull()) {
    $job->send();
    $queue->processed($job);
}
```

# Client API

## Connecting

When using the Client API directly (that is, using the commands provided by
`\Disque\Client`), you will have to connect manually.. You can connect via
the `connect()` method. As recommended by Disque, the connection is done 
as follows:

* The list of hosts is used to pick a random server.
* A connection is attempted against the picked server. If it fails, another
random node is tried.
* If a connection is successfull, the `HELLO` command is issued against this
server. If this fails, another random node is tried.
* If no connection is established and there are no servers left, a
`Disque\Connection\ConnectionException` is thrown.

Example call:

```php
$client = new \Disque\Client([
    '127.0.0.1:7711',
    '127.0.0.1:7712'
]);
$result = $client->connect();
var_dump($result);
```

The above `connect()` call will return an output similar to the following:

```php
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
$client->getConnectionManager()->setConnectionClass(\Disque\Connection\Predis::class);
```

### Smart node connection based on jobs being fetched

[Disque suggests](https://github.com/antirez/disque#client-libraries) that if a
consumer sees a high message rate received from a specific node, then clients
should connect to that node directly to reduce the number of messages between
nodes.

To achieve this, disquephp connection manager has a method that allows you to
specify how many jobs are required to be produced by a specific node before
we automatically switch connection to that node.

For example if we do:

```php
$disque = new \Disque\Client([
    '127.0.0.1:7711',
    '127.0.0.2:7712'
]);
$disque->connect();
```

We are currently connected to one of these nodes (no guarantee as to which one
since nodes are selected randomly.) Say that we are connected to the node at
port `7711`. By default no automatic connection change will take place, so we
will always be connected to the selected node. Say that we want to switch to
a node the moment a specific node produces at least 3 jobs. We first set this
option via the manager:

```php
$disque->getManager()->setMinimumJobsToChangeNode(3);
```

Now we process jobs as we normally do:

```php
while ($job = $disque->getJob()) {
    echo 'DO SOMETHING!';
    var_dump($job['body']);
    $disque->ackJob($job['id']);
}
```

If a specific node produces at least 3 jobs, the connection will automatically
switch to the node producing these many jobs. This is all done behind the
scenes, automatically.


Currently all Disque commands are implemented, and can be executed via the
`Disque\Client` class. Once you have established a connection, you can run
any of the following commands.

## ackJob

Acknowledges the execution of one or more jobs via job IDs. Signature:

```php
ackJob(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs acknowledged

Example call:

```php
$jobCount = $client->ackJob('jobid1', 'jobid2');
```

## addJob

Adds a job to the specified queue. Signature:

```php
addJob(string $queue, string $payload, array $options = []): string
```

Arguments:

* `$queue`: The name of the queue where to create the job. If no queue with
that name exists, it will be ceated automatically. Queues are also automatically
removed when they hold no pending jobs.
* `$payload`: Payload of the job. This is usually a JSON encoded set of arguments,
but you can specify whatever string you want.
* `$options`: Set of options, amongst:
  * `timeout`: an `int`, which specifies the timeout in milliseconds for the
    job. See [Disque's API](https://github.com/antirez/disque#api).
  * `replicate`: an`int`, to specify the number of nodes the job should be
    replicated to.
  * `delay`: an `int`, to specify the number of seconds that should elapse 
    before the job is queued by any server.
  * `retry`: an `int`, to specify the period (in seconds) after which, if the
    job is not acknowledged, the job is put again into the queue for delivery.
    See [Disque's API](https://github.com/antirez/disque#api).
  * `ttl`: an `int`, which is the maximum job life in seconds.
  * `maxlen`: an `int`, to specify that if there are already these many
    jobs queued in the given queue, then this new job is refused.
  * `async`: a `bool`, if `true`, tells the server to let the command return
    ASAP and replicate the job to the other nodes in background. See 
    [Disque's API](https://github.com/antirez/disque#api).

Return value:

* `string`: the job ID

Example call:

```php
$jobId = $client->addJob('queue', json_encode(['name' => 'Mariano']));
var_dump($jobId);
```

## delJob

Completely delete a job from a specific node. Signature:

```php
delJob(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs removed

Example call:

```php
$jobCount = $client->delJob('jobid1', 'jobid2');
```

## dequeue

Remove the given jobs from the queue. Signature:

```php
dequeue(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs dequeued

Example call:

```php
$jobCount = $client->dequeue('jobid1', 'jobid2');
```

## enqueue

Queue the given jobs, if not already queued. Signature:

```php
enqueue(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs enqueued

Example call:

```php
$jobCount = $client->enqueue('jobid1', 'jobid2');
```

## fastAck

Acknowledges the execution of one or more jobs via job IDs, using a faster
approach than `ACKJOB`. See [Disque's API](https://github.com/antirez/disque#api)
to understand the difference with `ACKJOB` and decide when to use which.
Signature:

```php
fastAck(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs acknowledged

Example call:

```php
$jobCount = $client->fastAck('jobid1', 'jobid2');
```

## getJob()

Gets a job (or several jobs if the option `count` is used) from the specified 
queue. Signature:

```php
getJob(string... $queues, array $options = []): array
```

Arguments:

* `$queues`: The set of queues from where to fetch jobs.
* `$options`: Set of options, amongst:
  * `timeout`: an `int`, which specifies the timeout in milliseconds to wait
    for jobs. If no jobs are available and this `timeout` expired, then no
    jobs are returned.
  * `count`: an `int`, to specify the number of jobs you wish to obtain.

Return value:

* `array`: A set of jobs, where each job is an indexed array with:
  * `queue`: a `string`, that indicates from which queue this job came from.
  * `id`: a `string`, which is the job ID.
  * `body`: a `string`, which is the payload of the job.

Example call:

```php
$jobs = $client->getJob('queue1', 'queue2', [
    'timeout' => 3000
]);
if (empty($jobs)) {
    die('NO JOBS!');
}

$job = $jobs[0];
echo "QUEUE: {$job['queue']}\n";
echo "ID: {$job['id']}\n";
var_dump(json_decode($job['body'], true));
```

## hello

Returns information from the connected node. You would normally not need to
use this, as it is using during the connection handshake. Signature:

```php
hello(): array
```

Arguments:

* None

Return value:

* `array`: Indexed array with:
  * `version`: a `string`, which indicates the `HELLO` format version.
  * `id`: a `string`, which is the ID of the Disque node we are connected to.
  * `nodes`: an `array`, which is a set of nodes, and where each node is an
    indexed array with:
    * `id`: a `string`, which is the ID of this Disque node.
    * `host`: a `string`, which is the host where this node is listening.
    * `port`: an `int`, which is the port where this node is listening.
    * `version`: a `string`, which indicates the `HELLO` format version.

Example call:

```php
$hello = $client->hello();
var_dump($hello);
```

## info

Get generic server information and statistics. You would normally not need to
use this. Signature:

```php
info(): string
```

Arguments:

* None

Return value:

* `string`: A big string with information about the connected node.

Example call:

```php
$info = $client->info();
echo $info;
```

## qlen

The length of the queue, that is, the number of jobs available in the given
queue. Signature:

```php
qlen(string $queue): int
```

Arguments:

* `$queue`: Queue from which to get the number of jobs available.

Return value:

* `int`: Queue length.

Example call:

```php
$count = $client->qlen('queue');
var_dump($hello);
```

## qpeek

Gets the given number of jobs from the given queue without consuming them (so
they will still be pending in the queue). Signature:

```php
qpeek(string $queue, int $count): array
```

Arguments:

* `$queue`: The queue from where to look for jobs.
* `count`: an `int`, to specify the number of jobs you wish to obtain. If this
    number is negative, then it will get these number of newest jobs.

Return value:

* `array`: A set of jobs, where each job is an indexed array with:
  * `id`: a `string`, which is the job ID.
  * `body`: a `string`, which is the payload of the job.

Example call:

```php
$jobs = $client->qpeek('queue', 1);
if (empty($jobs)) {
    die('NO JOBS!');
}

$job = $jobs[0];
echo "ID: {$job['id']}\n";
var_dump(json_decode($job['body'], true));
```

## show

Get information about the given job. Signature:

```php
show(string $id): array
```

Arguments:

* `string $id`: job ID

Return value:

* `array`: An indexed array with information about the job, including (but not
    limited to) `queue`, `state`, `ttl`, `delay`, `retry`, `body`,
    `nodes-delivered`, `nodes-confirmed`. See [Disque's API](https://github.com/antirez/disque#api).

Example call:

```php
$details = $client->show('jobid1');
var_dump($details);
```
