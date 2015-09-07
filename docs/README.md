# Installation

Install disque-php via Composer:

```bash
$ composer require mariano/disque-php --no-dev
```

If you want to run its tests remove the `--no-dev` argument.

# Creating the client

First you will need to create an instance of `Disque\Client`, specifying a list
of server credentials where different Disque nodes are installed:

```php
use Disque\Connection\Credentials;
use Disque\Client;

$nodes = [
    new Credentials('127.0.0.1', 7711),
    new Credentials('127.0.0.1', 7712, 'password'),
];

$disque = new Client($nodes);
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
from it. Jobs must implement the interface `Disque\Queue\JobInterface`, which
offers the following methods (among others used by the Queue API):

* `getId(): string`: gets the Disque job ID
* `setId(string $id)`: sets the job ID
* `getBody(): mixed`: gets the body of the job
* `setBody(mixed $body)`: sets the body of the job
* `getNacks(): int`: gets the number of NACKs the job has received
* `setNacks(int $nacks)`: sets the number of NACKs
* `getAdditionalDeliveries(): int`: gets the number of additional deliveries
* `setAdditionalDeliveries(int $ad)`: sets the number of additional deliveries

Job bodies can be of a mixed type and they can contain anything - an integer,
a string, an array. They get serialized into JSON by the JobMarshaler when
sending them to Disque.
If you want to change the default job implementation used in a queue, you can
do so as shown in the [Changing the Job class](#changing-the-job-class) section.

## Pushing jobs to the queue

The simplest way to push a job to the queue is by using its `push()` method:

```php
$job = new \Disque\Queue\Job(['name' => 'Mariano']);
$disque->queue('my_queue')->push($job);
```

You can specify different options that will affect how the job is placed on
the queue through its second, optional, argument `$options`. For available
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

You can obviously process as many jobs as there are already, and as they become
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
If this time passed and no job was available, `null` is returned. For example
if we want to wait for jobs, but do something else if after 1 second passed
without jobs becoming available, and then keep waiting for jobs, we would do:

```php
$queue = $disque->queue('my_queue');
while (true) {
    $job = $queue->pull(1000);
    if (is_null($job)) {
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

Once you have processed a job successfully, you will need to acknowledge it, to
avoid Disque from putting it back on the queue 
([details from Disque itself](https://github.com/antirez/disque#give-me-the-details)).
You do so via the `processed()` method, like so:

```php
$disque->queue('my_queue')->processed($job);
```

If the job failed and you want to retry it, send a negative acknowledgment
(or `NACK`) with the `failed()` method:

```php
$disque->queue('my_queue')->failed($job);
```

This will increase the NACK counter of the job. You can watch the counter and
if it crosses a threshold, ie. if the job has been retried too many times, you
can do something else than return it to the queue - move the job to a dead
letter queue, log it, notify someone etc.

### Jobs that consume a long time to process

If you are processing a job that requires a long time to be done, it is good
practice to call `processing()`, that way Disque is informed that we are still
working on the job, and avoids it from being requeued under the assumption
that the job could not be processed correctly. For example:

```php
$queue = $disque->queue('my_queue');
$job = $queue->pull();
for ($i=0; $i < 10; $i++) {
    // Every 2 seconds inform that we are working on the job
    $queue->processing($job);
}
// We are done with the job
$queue->processed($job);
```

## Changing the Job class

You can choose to have your own Job classes when using the Queue API. To do
so you start by implementing `Disque\Queue\JobInterface`, and make the class 
take whatever shape you deem necessary. You can also inherit the class
`Disque\Queue\BaseJob` which takes care of the basic setters and getters for
you. For example:

```php
use Disque\Queue\BaseJob;
use Disque\Queue\JobInterface;

class EmailJob extends BaseJob implements JobInterface
{
    public $email;
    public $subject;
    public $message;

    public function __construct($email, $subject, $message)
    {
        $this->email = $email;
        $this->subject = $subject;
        $this->message = $message ?: 'No message';
    }

    public function send()
    {
        echo "SEND EMAIL TO {$this->email}:\n";
        echo $this->subject . "\n\n";
        echo $this->message;
    }
}
```

You then have to create a Marshaler: a way for this job class to be serialized,
and deserialized. Marshalers should implement 
`Disque\Queue\Marshal\MarshalerInterface`. You would normally use JSON, but
you are not required to. For example to create a marshaler for the above
`EmailJob` class we could do:

```php
use Disque\Queue\Marshal\MarshalerInterface;

class EmailJobMarshaler implements MarshalerInterface
{
    public function unmarshal($source)
    {
        $body = @json_decode($source, true);
        if (is_null($body)) {
            throw new \Disque\Queue\Marshal\MarshalException("Could not deserialize {$source}");
        } elseif (!is_array($body) || empty($body['email']) || empty($body['subject'])) {
            throw new \Disque\Queue\Marshal\MarshalException('Not an email job');
        }
        $body += ['message' => null];
        return new EmailJob($body['email'], $body['subject'], $body['message']);
    }

    public function marshal(\Disque\Queue\JobInterface $job)
    {
        if (!($job instanceof EmailJob)) {
            throw new \Disque\Queue\Marshal\MarshalException('Not an email job');
        }
        return json_encode([
            'email' => $job->email,
            'subject' => $job->subject,
            'message' => $job->message
        ]);
    }
}
```

As you can see `unmarshal()` will take a string, and should return an
instance of `Disque\Queue\JobInterface`, or throw a 
`Disque\Queue\Marshal\MarshalException` if something went wrong. Similarly
`marshal()` takes a `Disque\Queue\JobInterface` and returns its string
representation, throwing a `Disque\Queue\Marshal\MarshalException` if something
went wrong.

To use this marshaler you create an instance of it, and set it via the Queue
`setMarshaler()` method:

```php
$queue = $disque->queue('emails');
$queue->setMarshaler(new EmailJobMarshaler());
```

You can now push jobs to the queue:

```php
$job = new EmailJob('claudia@example.com', 'Hello world!', 'Hello from Disque :)');
$queue->push($job);
echo "JOB #{$job->getId()} pushed!\n";
```

When pulling jobs from the queue, you can take advantage of your custom job
implementation:

```php
while ($job = $queue->pull()) {
    echo "Got JOB #{$job->getId()}!\n";
    $job->send();
    $queue->processed($job);
}
```

This is just an example. For more complicated tasks consider following
the single responsibility principle, using the Job object only to transport
the job data and doing the actual work in a dedicated worker class.

# Client API

## Connecting

When using the Client API directly (that is, using the commands provided by
`\Disque\Client`), you will have to connect manually. You can connect via
the `connect()` method. As recommended by Disque, the connection is done 
as follows:

* The list of hosts is used to pick a random server.
* A connection is attempted against the picked server. If it fails, another
random node is tried.
* If a connection is successful, the `HELLO` command is issued against this
server. If this fails, another random node is tried.
* If no connection is established and there are no servers left, a
`Disque\Connection\ConnectionException` is thrown.

Example call:

```php
use Disque\Connection\Credentials;
use Disque\Client;

$nodes = [
    new Credentials('127.0.0.1', 7711),
    new Credentials('127.0.0.1', 7712, 'password'),
];

$disque = new Client($nodes);

$result = $disque->connect();
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
so via the `setConnectionFactory()` method. For example, if you wish
to use [predis](https://github.com/nrk/predis) (maybe because you are already
using its PHP extension), you would first add predis to your Composer
requirements:

```bash
$ composer require predis/predis --no-dev
```

And then inject the connection factory:

```php
$connectionFactory = new Disque\Connection\Factory\PredisFactory();
$client->getConnectionManager()->setConnectionFactory($connectionFactory);
```

### Node priority - Choosing the best node to switch to

[Disque suggests](https://github.com/antirez/disque#client-libraries) that if a
consumer sees a high message rate received from a specific node, then clients
should connect to that node directly to reduce the number of messages between
nodes. For example imagine the following cluster:

```php
use Disque\Connection\Credentials;
use Disque\Client;

$nodes = [
    new Credentials('127.0.0.1', 7711),
    new Credentials('127.0.0.1', 7712),
];

$disque = new Client($nodes);
$disque->connect();
```

We are currently connected to one of these nodes (no guarantee as to which one
since nodes are selected randomly). Say that we are connected to the node at
port `7711`. Now we process jobs as we normally do:

```php
while ($job = $disque->getJob()) {
    echo 'DO SOMETHING!';
    var_dump($job['body']);
    $disque->ackJob($job['id']);
}
```

If the node at port `7712` produces more jobs than our current node,
the connection will automatically switch to the node producing these jobs.
This is all done behind the scenes, automatically.

To achieve this:

* The disquephp connection manager watches how many jobs a given node has 
 produced so far and updates the stats for each node
* It asks a node prioritizer regularly, whether it should switch to a better
 node. The prioritizer gets a list of all nodes with their stats and decides.
* The connection manager gets a list of nodes sorted by priority and it tries 
 to switch to the best available node.
 
There are currently two stats that the nodes are tracking:

* The number of jobs produced altogether
* The number of jobs produced since the last switch

The default strategy is the conservative job count strategy, implemented
in the class `Disque\Connection\Node\ConservativeJobCountPrioritizer`. It
switches to a new node if this node has produced more jobs than the current
node, but only if the number of jobs is 5% higher than the number of jobs
produced by the current node. This safety margin stops the manager
from switching if the difference is too small.
You can change the safety margin in this strategy. For example to set
the safety margin to 15%, you would call:
```php
$connectionManager = $client->getConnectionManager();
$connectionManager->getPriorityStrategy()->setMarginToSwitch(0.15);
```

There are two other prioritizing strategies:
 
* The null strategy, implemented in `Disque\Connection\Node\NullPrioritizer`
 never switches.
* The random strategy, `Disque\Connection\Node\RandomPrioritizer` switches
to nodes randomly. This may be useful for testing your cluster.

If you would like to write your own strategy, implement the interface
`Disque\Connection\Node\NodePrioritizerInterface` and inject the strategy
into the manager by calling
`$connectionManager->setPriorityStrategy($customStrategy);`

If you would like to track other stats for your nodes, for example latency,
inherit the `Disque\Connection\Node\Node` to add setters and getters for
the new stats, then inherit the `Disque\Connection\Manager` and override its
methods
`Manager::preprocessExecution()` and
`Manager::postprocessExecution()`

Here you can update the node stats.

Finally, inject the new Manager into the Client via
`$client->setConnectionManager($customManager);`

## Commands

Currently all Disque commands are implemented, and can be executed via the
`Disque\Client` class. Once you have established a connection, you can run
any of the following commands.

### ackJob

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

### addJob

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

### delJob

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

### dequeue

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

### enqueue

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

### fastAck

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

### getJob

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
  * `withcounters`: a `bool`, if `true`, will fetch the jobs
     with the `WITHCOUNTERS` argument. The jobs will then contain two
     additional fields, the counters `nacks` and `additional-deliveries`
     for failure handling.

Return value:

* `array`: A set of jobs, where each job is an indexed array with:
  * `queue`: a `string`, that indicates from which queue this job came from.
  * `id`: a `string`, which is the job ID.
  * `body`: a `string`, which is the payload of the job.
  * `nacks`: an `int`, the number of `NACKs` received by the job
  * `additional-deliveries`: an `int`, the number of additional deliveries
     performed for this job
  
The last two fields, `nacks` and `additional-deliveries`, will only be present
if you call the command with the `withcounters` argument.

What do these two counters mean?

The `nacks` counter is incremented every time a worker uses the `NACK` command
to tell the queue the job was not processed correctly and should be put back
on the queue.

The `additional-deliveries` counter is incremented for every other condition
(different than `NACK` call) that requires a job to be put back on the
queue again. This includes jobs that get lost and are enqueued again or
jobs that are delivered multiple times because they time out.


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

### hello

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

### info

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

### nack

Put the job(s) back to the queue immediately and increment the nack counter.

The command should be used when the worker was not able to process a job and
wants the job to be put back into the queue in order to be processed again.

It is very similar to ENQUEUE but it increments the job nacks counter
instead of the additional-deliveries counter.

```php
nack(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs nacked

Example call:

```php
$jobCount = $client->nack('jobid1', 'jobid2');
```


### qlen

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

### qpeek

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
  * `queue`: a `string`, that indicates from which queue this job came from.
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

### qscan

Iterate all existing queues on the node that the client is connected to,
allowing navigation with a cursor. As specified by Disque this command may
return duplicated elements.

```php
qscan(int $cursor = 0, array $options = [])
```

Arguments:

* `$cursor`: an `int`, which is the cursor we are navigating. On first call 
    this should be `0`, when following an already established cursor this 
    should be the cursor returned by the previous call (see `nextCursor`).
* `$options`: an array, containing the set of options to influence the scan.
    Available options:
  * `count`: an `int`, a hint about how much work to do per iteration.
  * `busyloop`: a `bool`. If set to `true` the call will block and will return
    all elements in a single iteration.
  * `minlen`: an `int`. Do not include any queues with less than the given
    number of jobs queued.
  * `maxlen`: an `int`. Do not include any queues with more than the given
    number of jobs queued.
  * `importrate`: an `int`. Only include queues with a job import rate (from
    other nodes) higher than or equal to the given number.

Return value:

* `array`: An indexed array with:
  * `finished`: a `bool`, which tells if this is the last iteration.
  * `nextCursor`: an `int`, which tells the cursor to use to get the next
    iteration. If `0` then this is the last iteration (which also guarantees
    that `finished` is set to `true`).
  * `queues`: an `array`, where each element is a queue name.

Example call:

```php
// Get all queues, one queue at a time
$cursor = 0;
do {
    $result = $client->qscan($cursor, ['count' => 1]);
    var_dump($result['queues']);
    $cursor = $result['nextCursor'];
} while (!$result['finished']);
```

### show

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

### working

Claims to be still working with the specified job, and asks Disque to postpone 
the next time it will deliver again the job. Signature:

```php
working(string $id): int
```

Arguments:

* `string $id`: job ID

Return value:

* `int`: Number of seconds you (likely) postponed the message visibility for
    other workers. See [Disque's API](https://github.com/antirez/disque#api).

Example call:

```php
$seconds = $client->working('jobid1');
var_dump($seconds);
```
