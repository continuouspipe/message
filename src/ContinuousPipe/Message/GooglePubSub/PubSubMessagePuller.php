<?php

namespace ContinuousPipe\Message\GooglePubSub;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageDeadlineExpirationManager;
use ContinuousPipe\Message\MessageException;
use ContinuousPipe\Message\MessagePuller;
use Google\Cloud\Exception\GoogleException;
use Google\Cloud\PubSub\Connection\ConnectionInterface;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\ServiceBuilder;
use JMS\Serializer\Exception\Exception as SerializerException;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class PubSubMessagePuller implements MessagePuller, MessageDeadlineExpirationManager
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
        $subscription = $this->getSubscription();

        try {
            foreach ($subscription->pull(['returnImmediately' => false, 'maxMessages' => 1,]) as $googleCloudMessage) {
                /** @var \Google\Cloud\PubSub\Message $googleCloudMessage */

                try {
                    $message = $this->serializer->deserialize(
                        $googleCloudMessage->data(),
                        $googleCloudMessage->attribute('class'),
                        'json'
                    );

                    yield new PubSubPulledMessage($subscription, $googleCloudMessage, $message);
                } catch (SerializerException $e) {
                    $this->logger->error('Unable to unserialize message', [
                        'message' => $googleCloudMessage->id(),
                        'exception' => $e,
                    ]);

                    $subscription->acknowledge($googleCloudMessage);
                }
            }
        } catch (GoogleException $e) {
            throw new MessageException('Unable to pull messages', $e->getCode(), $e);
        }
    }

    public function modifyDeadline(string $messageIdentifier, int $seconds)
    {
        $subscription = $this->getSubscription();
        $connectionGetter = \Closure::bind(function (Subscription $subscription) {
            return $subscription->connection;
        }, null, $subscription);

        /** @var ConnectionInterface $connection */
        $connection = $connectionGetter($subscription);
        $connection->modifyAckDeadline([
            'subscription' => $this->subscriptionName,
            'ackIds' => [$messageIdentifier],
            'ackDeadlineSeconds' => $seconds
        ]);
    }

    private function getSubscription(): Subscription
    {
        $pubSub = $this->serviceBuilder->pubsub();

        return $pubSub->subscription($this->subscriptionName);
    }
}
