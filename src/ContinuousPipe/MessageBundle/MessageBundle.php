<?php

namespace ContinuousPipe\MessageBundle;

use ContinuousPipe\MessageBundle\DependencyInjection\CompilerPass\SimpleBusAsynchronousCommandBusIsPublic;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MessageBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SimpleBusAsynchronousCommandBusIsPublic());
    }
}
