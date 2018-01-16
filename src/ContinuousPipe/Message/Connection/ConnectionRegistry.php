<?php

namespace ContinuousPipe\Message\Connection;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ConnectionRegistry
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function byName(string $name) : Connection
    {
        $connectionServiceName = 'continuouspipe.message.'.$name;
        $connection = $this->container->get($connectionServiceName);

        if (!$connection instanceof Connection) {
            throw new \RuntimeException(sprintf(
                'Service "%s" is not a connection',
                $connectionServiceName
            ));
        }

        return $connection;
    }
}
