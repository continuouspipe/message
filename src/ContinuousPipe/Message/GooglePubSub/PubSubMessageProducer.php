<?php

namespace ContinuousPipe\Message\GooglePubSub;

use ContinuousPipe\Message\Delay\DelayedMessage;
use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageProducer;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Core\ServiceBuilder;
use JMS\Serializer\SerializerInterface;

class PubSubMessageProducer implements MessageProducer
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ServiceBuilder
     */
    private $serviceBuilder;

    /**
     * @var string
     */
    private $topicName;

    public function __construct(SerializerInterface $serializer, ServiceBuilder $serviceBuilder, string $topicName)
    {
        $this->serializer = $serializer;
        $this->serviceBuilder = $serviceBuilder;
        $this->topicName = $topicName;
    }

    public function produce(Message $message)
    {
        $attributes = [
            'class' => get_class($message),
        ];

        if ($message instanceof DelayedMessage) {
            $attributes['delayed_until'] = $message->runAt()->format(\DateTime::ISO8601);
        }

        try {
            $this->serviceBuilder->pubsub()->topic($this->topicName)->publish([
                'data' => $this->serializer->serialize($message, 'json'),
                'attributes' => $attributes,
            ]);
        } catch (GoogleException $e) {
            throw $e;
        }
    }
}
