<?php
namespace Disque\Connection\Response;

use Closure;

abstract class BaseResponse
{
    /**
     * Data
     *
     * @var string
     */
    protected $data;

    /**
     * Reader
     *
     * @var Closure
     */
    protected $reader;

    /**
     * Receiver
     *
     * @var Closure
     */
    protected $receiver;

    /**
     * Create instance
     *
     * @param string $data
     */
    public function __construct($data)
    {
        $this->data = substr($data, 0, -2); // Get rid of last CRLF
    }

    /**
     * Set reader
     *
     * @param Closure $reader Reader function
     * @return void
     */
    public function setReader(Closure $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Set receiver
     *
     * @param Closure $receiver Receiver function
     * @return void
     */
    public function setReceiver(Closure $receiver)
    {
        $this->receiver = $receiver;
    }

    /**
     * Parse response
     *
     * @return mixed Response
     */
    abstract public function parse();
}