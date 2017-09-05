<?php

namespace ContinuousPipe\Message;

use Symfony\Component\DependencyInjection\ContainerInterface;

class MessagePullerRegistry
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function pullerForConnection(string $connectionName) : MessagePuller
    {
        $pullerServiceName = 'continuouspipe.message.'.$connectionName.'.message_puller';
        $puller = $this->container->get($pullerServiceName);

        if (!$puller instanceof MessagePuller) {
            throw new \RuntimeException(sprintf(
                'Service "%s" is not a valid message puller',
                $pullerServiceName
            ));
        }

        return $puller;
    }
}
