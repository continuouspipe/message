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
                                        return is_array($values) && count($values) !== 1;
                                    })
                                    ->thenInvalid('Only one driver should be configured')
                                ->end()
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($dsn) {
                                        return self::parseDsn($dsn);
                                    })
                                ->end()
                                ->children()
                                    ->scalarNode('direct')->end()
                                    ->arrayNode('google_pub_sub')
                                        ->children()
                                            ->scalarNode('project_id')->isRequired()->end()
                                            ->scalarNode('service_account_path')->isRequired()->end()
                                            ->scalarNode('topic')->isRequired()->end()
                                            ->scalarNode('subscription')->isRequired()->end()
                                            ->arrayNode('options')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
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

    public static function parseDsn($dsn)
    {
        $url = parse_url($dsn);
        $options = null;
        if (isset($url['query'])) {
            parse_str($url['query'], $options);
        }

        switch ($url['scheme']) {
            case 'gps':
                $driver = [
                    'google_pub_sub' => [
                        'project_id' => $url['user'],
                        'service_account_path' => $url['pass'],
                        'subscription' => $url['host'],
                        'topic' => substr($url['path'], 1),
                    ]
                ];

                if ($options) {
                    $driver['google_pub_sub']['options'] = $options;
                }

                break;
            default:
                throw new \InvalidArgumentException(sprintf(
                    'Type "%s" is not parsable',
                    $url['scheme']
                ));
        }

        return $driver;
    }
}
