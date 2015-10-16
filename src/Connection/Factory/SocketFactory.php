<?php
namespace Disque\Connection\Factory;

use Disque\Connection\Socket;

/**
 * Create the default Disque connection
 */
class SocketFactory implements ConnectionFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function create($host, $port)
    {
        return new Socket($host, $port);
    }
}
