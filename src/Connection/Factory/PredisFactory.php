<?php
namespace Disque\Connection\Factory;

use Disque\Connection\Predis;

/**
 * Create the default Disque connection
 */
class PredisFactory implements ConnectionFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function create($host, $port)
    {
        return new Predis($host, $port);
    }
}
