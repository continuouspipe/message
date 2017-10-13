<?php

namespace ContinuousPipe\MessageBundle\DependencyInjection;

use ContinuousPipe\Message\Debug\TracedMessageProducer;
use ContinuousPipe\Message\Direct\DelayedMessagesBuffer;
use ContinuousPipe\Message\Direct\FromProducerToConsumer;
use ContinuousPipe\Message\GooglePubSub\PubSubMessageProducer;
use ContinuousPipe\Message\GooglePubSub\PubSubMessagePuller;
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
                $container->getDefinition('continuouspipe.message.command.transaction_manager.modify_deadline_for_delayed_messages')->replaceArgument(1, new Reference(
                    $config['command']['message_deadline_expiration_manager']
                ));
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
        }

        $drivers = [];
        foreach ($config['connections'] as $name => $connection) {
            $this->createConnection($container, $name, $connection);

            $drivers = array_unique(array_merge($drivers, array_keys($connection['driver'])));
        }

        foreach ($drivers as $driver) {
            if (in_array($driver, ['google_pub_sub'])) {
                $loader->load('drivers/' . $driver . '.xml');
            }
        }

        if ($config['tideways']['enabled']) {
            $container->setParameter('continuous_pipe.message.tideways_api_key', $config['tideways']['api_key']);

            $loader->load('integrations/tideways.xml');
        }
    }

    private function createConnection(ContainerBuilder $container, string $name, array $configuration)
    {
        $driverConfiguration = $configuration['driver'];

        $pullerName = $this->getConnectionPullerName($name);
        $producerName = $this->getConnectionProducerName($name);

        if (array_key_exists('direct', $driverConfiguration)) {
            $this->createDirectConnection($container, $pullerName, $producerName);
        } elseif (array_key_exists('google_pub_sub', $driverConfiguration)) {
            $this->createGooglePubSubConnection($container, $pullerName, $producerName, $driverConfiguration['google_pub_sub']);
        } elseif (array_key_exists('router', $driverConfiguration)) {
            $this->createRouterConnection($container, $pullerName, $producerName, $driverConfiguration['router']);
        } else {
            throw new \RuntimeException(sprintf(
                'Driver not found with the following configuration keys: %s',
                implode(', ', array_keys($driverConfiguration))
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

    private function createDirectConnection(ContainerBuilder $container, string $pullerName, string $producerName)
    {
        $container->setDefinition(
            $pullerName,
            new Definition(ArrayMessagePuller::class)
        );

        $container->setDefinition(
            $producerName,
            new Definition(FromProducerToConsumer::class, [
                new Reference('continuouspipe.message.message_consumer'),
                new Reference('jms_serializer'),
            ])
        );

        $container->setDefinition(
            $producerName . '.delayed_messages_buffer',
            (new Definition(DelayedMessagesBuffer::class, [
                new Reference($producerName . '.delayed_messages_buffer.inner'),
            ]))->setDecoratedService($producerName)
        );
    }

    private function createGooglePubSubConnection(ContainerBuilder $container, string $pullerName, string $producerName, array $driverConfiguration)
    {
        $container->setDefinition(
            $pullerName,
            new Definition(PubSubMessagePuller::class, [
                new Reference('jms_serializer'),
                new Reference('logger'),
                $driverConfiguration['project_id'],
                $driverConfiguration['service_account_path'],
                $driverConfiguration['topic'],
                $driverConfiguration['subscription'],
                $driverConfiguration['options']
            ])
        );

        $container->setDefinition(
            $producerName . '.service_builder',
            new Definition(ServiceBuilder::class, [
                [
                    'projectId' => $driverConfiguration['project_id'],
                    'keyFilePath' => $driverConfiguration['service_account_path'],
                ]
            ])
        );

        $container->setDefinition(
            $producerName,
            new Definition(PubSubMessageProducer::class, [
                new Reference('jms_serializer'),
                new Reference($producerName . '.service_builder'),
                $driverConfiguration['topic']
            ])
        );
    }

    private function createRouterConnection(ContainerBuilder $container, string $pullerName, string $producerName, array $driverConfiguration)
    {
        $container->setDefinition($producerName, new Definition(RoutedMessageProducer::class, [
            array_map(function (array $configuration) {
                return new Reference($this->getConnectionProducerName($configuration['connection']));
            }, $driverConfiguration['message_to_connection_mapping']),
        ]));
    }

    private function getConnectionPullerName(string $name): string
    {
        return 'continuouspipe.message.' . $name . '.message_puller';
    }

    private function getConnectionProducerName(string $name): string
    {
        return 'continuouspipe.message.' . $name . '.message_producer';
    }
}
