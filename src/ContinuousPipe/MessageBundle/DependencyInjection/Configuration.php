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
                ->arrayNode('connections')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('driver')
                                ->isRequired()
                                ->performNoDeepMerging()
                                ->validate()
                                    ->ifTrue(function ($values) {
                                        return isset($values['router']) && isset($values['dsn']);
                                    })
                                    ->thenInvalid('You can either configure the router or the driver DSN for a given connection.')
                                ->end()
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($dsn) {
                                        return [
                                            'dsn' => $dsn,
                                        ];
                                    })
                                ->end()
                                ->children()
                                    ->scalarNode('dsn')->end()
                                    ->arrayNode('options')
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->arrayNode('router')
                                        ->children()
                                            ->arrayNode('message_to_connection_mapping')
                                                ->useAttributeAsKey('message_class')
                                                ->prototype('array')
                                                    ->beforeNormalization()
                                                        ->ifString()
                                                        ->then(function ($v) {
                                                            return array('connection' => $v);
                                                        })
                                                    ->end()
                                                    ->children()
                                                        ->scalarNode('connection')->isRequired()->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->booleanNode('debug')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('simple_bus')
                    ->children()
                        ->scalarNode('connection')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('command')
                    ->children()
                        ->scalarNode('connection')->defaultValue(null)->end()
                        ->scalarNode('message_deadline_expiration_manager')->end()
                        ->booleanNode('allow_multiple_extenders')->defaultTrue()->end()
                        ->arrayNode('retry_exceptions')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('tideways')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('api_key')->isRequired()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
