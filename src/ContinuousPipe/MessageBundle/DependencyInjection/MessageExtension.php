<?php

namespace ContinuousPipe\MessageBundle\DependencyInjection;

use ContinuousPipe\Message\AutoRetry\CatchGivenExceptions;
use ContinuousPipe\Message\Connection\Connection;
use ContinuousPipe\Message\Debug\TracedMessageProducer;
use ContinuousPipe\Message\Direct\DelayedMessagesBuffer;
use ContinuousPipe\Message\Direct\FromProducerToConsumer;
use ContinuousPipe\Message\GooglePubSub\PubSubMessageProducer;
use ContinuousPipe\Message\GooglePubSub\PubSubMessagePuller;
use ContinuousPipe\Message\MessageProducer;
use ContinuousPipe\Message\MessagePuller;
use ContinuousPipe\Message\InMemory\ArrayMessagePuller;
use ContinuousPipe\Message\Router\RoutedMessageProducer;
use Google\Cloud\ServiceBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class MessageExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        if (isset($config['simple_bus'])) {
            $loader->load('simple-bus.xml');

            $container->getDefinition('continuouspipe.message.simple_bus.producer')->replaceArgument(0, new Reference(
                'continuouspipe.message.' . $config['simple_bus']['connection'] . '.message_producer'
            ));
        }

        if (isset($config['command'])) {
            $loader->load('command.xml');

            if (isset($config['command']['message_deadline_expiration_manager'])) {
                $container->getDefinition('continuouspipe.message.command.transaction_manager.modify_deadline_for_delayed_messages')->replaceArgument(
                    1,
                    new Reference($config['command']['message_deadline_expiration_manager'])
                );
            } else {
                $container->removeDefinition('continuouspipe.message.command.transaction_manager.modify_deadline_for_delayed_messages');
            }

            if (isset($config['command']['connection'])) {
                $commandPuller = new Reference('continuouspipe.message.' . $config['command']['connection'] . '.message_puller');
            } else {
                $commandPuller = new Reference('continuouspipe.message.puller_registry');
            }

            $container
                ->getDefinition('continuouspipe.message.command.pull_and_consumer')
                ->replaceArgument(0, $commandPuller)
            ;

            $container
                ->getDefinition('continuouspipe.message.command.transaction_manager.message_extender_factory')
                ->setArgument(1, $config['command']['allow_multiple_extenders'])
            ;

            if (!empty($config['command']['retry_exceptions'])) {
                $container->setDefinition('continuouspipe.message.command.pull_and_consumer.throwable_catcher', new Definition(
                    CatchGivenExceptions::class,
                    [
                        $config['command']['retry_exceptions'],
                    ]
                ));
            }
        }

        foreach ($config['connections'] as $name => $connection) {
            $this->createConnection($container, $name, $connection);
        }

        $loader->load('services.xml');
        $loader->load('drivers/google_pub_sub.xml');

        if ($config['tideways']['enabled']) {
            $container->setParameter('continuous_pipe.message.tideways_api_key', $config['tideways']['api_key']);

            $loader->load('integrations/tideways.xml');
        }
    }

    private function createConnection(ContainerBuilder $container, string $name, array $configuration)
    {
        $driverConfiguration = $configuration['driver'];

        $connectionName = $this->getConnectionServiceName($name);
        $pullerName = $connectionName.'.message_puller';
        $producerName = $connectionName.'.message_producer';

        if (array_key_exists('router', $driverConfiguration)) {
            $container->setDefinition($producerName, new Definition(RoutedMessageProducer::class, [
                array_map(function (array $configuration) {
                    return new Reference($this->getConnectionServiceName($configuration['connection']).'.message_producer');
                }, $driverConfiguration['router']['message_to_connection_mapping']),
            ]));
        } else {
            $container->setDefinition($connectionName, (
                (new Definition(Connection::class, [
                    array_merge([
                        'dsn' => $driverConfiguration['dsn'],
                    ], $driverConfiguration['options'])
                ]))
                ->setFactory([new Reference('continuouspipe.message.dsn_connection_factory'), 'create'])
            ));

            $container->setDefinition($pullerName, (
                (new Definition(MessagePuller::class))
                ->setFactory([new Reference($connectionName), 'getPuller'])
            ));
            $container->setDefinition($producerName, (
                (new Definition(MessageProducer::class))
                ->setFactory([new Reference($connectionName), 'getProducer'])
            ));
        }

        if ($configuration['debug']) {
            $container->setDefinition($producerName . '.traced', (new Definition(
                TracedMessageProducer::class,
                [
                    new Reference($producerName . '.traced.inner')
                ]
            ))->setDecoratedService($producerName));
        }
    }

    private function getConnectionServiceName(string $name): string
    {
        return 'continuouspipe.message.'.$name;
    }
}
