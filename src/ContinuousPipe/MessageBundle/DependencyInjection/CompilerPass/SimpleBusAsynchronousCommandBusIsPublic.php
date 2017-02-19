<?php

namespace ContinuousPipe\MessageBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SimpleBusAsynchronousCommandBusIsPublic implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('simple_bus.asynchronous.command_bus')->setPublic(true);
    }
}
