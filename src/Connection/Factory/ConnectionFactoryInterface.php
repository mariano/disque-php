<?php
namespace Disque\Connection\Factory;

interface ConnectionFactoryInterface
{
    /**
     * Create a new Connection object
     *
     * @param string $host
     * @param int    $port
     *
     * @return ConnectionInterface
     */
    public function create($host, $port);
}
