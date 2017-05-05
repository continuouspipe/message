<?php

namespace ContinuousPipe\MessageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder
            ->root('message')
            ->children()
                ->enumNode('driver')
                    ->isRequired()
                    ->values(['google-pub-sub', 'direct', 'none'])
                ->end()
            ->end()
        ;

        return $builder;
    }
}
