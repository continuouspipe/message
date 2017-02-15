<?php

namespace ContinuousPipe\Message\GooglePubSub;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageProducer;
use Google\Cloud\Exception\GoogleException;
use Google\Cloud\ServiceBuilder;
use JMS\Serializer\SerializerInterface;

class PubSubMessageProducer implements MessageProducer
{
    /**
     * @var ServiceBuilder
     */
    private $serviceBuilder;
    /**
     * @var string
     */
    private $topicName;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer, string $projectId, string $keyFilePath, string $topicName)
    {
        $this->serializer = $serializer;
        $this->topicName = $topicName;
        $this->serviceBuilder = new ServiceBuilder([
            'projectId' => $projectId,
            'keyFilePath' => $keyFilePath,
        ]);
    }

    public function produce(Message $message)
    {
        try {
            $this->serviceBuilder->pubsub()->topic($this->topicName)->publish([
                'data' => $this->serializer->serialize($message, 'json'),
                'attributes' => [
                    'class' => get_class($message),
                ],
            ]);
        } catch (GoogleException $e) {
            throw $e;
        }
    }
}
