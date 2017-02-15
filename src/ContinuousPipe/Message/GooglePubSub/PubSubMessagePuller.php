<?php

namespace ContinuousPipe\Message\GooglePubSub;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageException;
use ContinuousPipe\Message\MessagePuller;
use Google\Cloud\Exception\GoogleException;
use Google\Cloud\ServiceBuilder;
use JMS\Serializer\Exception\Exception as SerializerException;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class PubSubMessagePuller implements MessagePuller
{
    /**
     * @var ServiceBuilder
     */
    private $serviceBuilder;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $topicName;
    /**
     * @var string
     */
    private $subscriptionName;

    public function __construct(SerializerInterface $serializer, LoggerInterface $logger, string $projectId, string $keyFilePath, string $topicName, string $subscriptionName)
    {
        $this->serializer = $serializer;
        $this->topicName = $topicName;
        $this->serviceBuilder = new ServiceBuilder([
            'projectId' => $projectId,
            'keyFilePath' => $keyFilePath,
        ]);
        $this->subscriptionName = $subscriptionName;
        $this->logger = $logger;
    }

    public function pull(): \Generator
    {
        $pubSub = $this->serviceBuilder->pubsub();
        $subscription = $pubSub->subscription($this->subscriptionName);

        try {
            foreach ($subscription->pull(['returnImmediately' => false]) as $googleCloudMessage) {
                /** @var \Google\Cloud\PubSub\Message $googleCloudMessage */

                try {
                    $message = $this->serializer->deserialize(
                        $googleCloudMessage->data(),
                        $googleCloudMessage->attribute('class'),
                        'json'
                    );

                    yield new PubSubPulledMessage($subscription, $googleCloudMessage, $message);
                } catch (SerializerException $e) {
                    throw new MessageException('Unable to unserialize message', $e->getCode(), $e);
                }
            }
        } catch (GoogleException $e) {
            throw new MessageException('Unable to pull messages', $e->getCode(), $e);
        }
    }
}
