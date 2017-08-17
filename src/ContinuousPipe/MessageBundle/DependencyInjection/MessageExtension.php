<?php

namespace ContinuousPipe\MessageBundle\DependencyInjection;

use ContinuousPipe\Message\Debug\TracedMessageProducer;
use ContinuousPipe\Message\Direct\DelayedMessagesBuffer;
use ContinuousPipe\Message\Direct\FromProducerToConsumer;
use ContinuousPipe\Message\GooglePubSub\PubSubMessageProducer;
use ContinuousPipe\Message\GooglePubSub\PubSubMessagePuller;
use ContinuousPipe\Message\InMemory\ArrayMessagePuller;
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

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (isset($config['simple_bus'])) {
            $loader->load('simple-bus.xml');

            $container->getDefinition('continuouspipe.message.simple_bus.producer')->replaceArgument(0, new Reference(
                'continuouspipe.message.'.$config['simple_bus']['connection'].'.message_producer'
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

            $container
                ->getDefinition('continuouspipe.message.command.pull_and_consumer')
                ->replaceArgument(0, new Reference('continuouspipe.message.'.$config['command']['connection'].'.message_puller'))
            ;
        }

        $drivers = [];
        foreach ($config['connections'] as $name => $connection) {
            $this->createConnection($container, $name, $connection);

            $drivers = array_unique(array_merge($drivers, array_keys($connection['driver'])));
        }

        foreach ($drivers as $driver) {
            if (file_exists($filePath = 'drivers/'.$driver.'.xml')) {
                $loader->load($filePath);
            }
        }
    }

    private function createConnection(ContainerBuilder $container, string $name, array $configuration)
    {
        $driverConfiguration = $configuration['driver'];

        $pullerName = 'continuouspipe.message.' . $name . '.message_puller';
        $producerName = 'continuouspipe.message.' . $name . '.message_producer';

        if (array_key_exists('direct', $driverConfiguration)) {
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
                $producerName.'.delayed_messages_buffer',
                (new Definition(DelayedMessagesBuffer::class, [
                    new Reference($producerName.'.delayed_messages_buffer.inner'),
                ]))->setDecoratedService($producerName)
            );
        }

        if (array_key_exists('google_pub_sub', $driverConfiguration)) {
            $container->setDefinition(
                $pullerName,
                new Definition(PubSubMessagePuller::class, [
                    new Reference('jms_serializer'),
                    new Reference('logger'),
                    $driverConfiguration['google_pub_sub']['project_id'],
                    $driverConfiguration['google_pub_sub']['service_account_path'],
                    $driverConfiguration['google_pub_sub']['topic'],
                    $driverConfiguration['google_pub_sub']['subscription']
                ])
            );

            $container->setDefinition(
                $producerName,
                new Definition(PubSubMessageProducer::class, [
                    new Reference('jms_serializer'),
                    $driverConfiguration['google_pub_sub']['project_id'],
                    $driverConfiguration['google_pub_sub']['service_account_path'],
                    $driverConfiguration['google_pub_sub']['topic']
                ])
            );
        }

        if ($configuration['debug']) {
            $container->setDefinition($producerName.'.traced', (new Definition(
                TracedMessageProducer::class, [
                    new Reference($producerName.'.traced.inner')
                ]
            ))->setDecoratedService($producerName));
        }
    }
}
