<?php

namespace ContinuousPipe\Message\Bridge\Enqueue;

use ContinuousPipe\Message\Command\ReceivedMessage;
use ContinuousPipe\Message\MessageDeadlineExpirationManager;
use ContinuousPipe\Message\MessagePuller;
use Enqueue\AmqpExt\AmqpContext;
use Interop\Amqp\AmqpQueue;
use JMS\Serializer\Exception\Exception as SerializerException;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class EnqueueMessagePuller implements MessagePuller, MessageDeadlineExpirationManager
{
    private $context;
    private $queueName;
    private $serializer;
    private $logger;

    public function __construct(SerializerInterface $serializer, LoggerInterface $logger, AmqpContext $context, string $queueName)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->context = $context;
        $this->queueName = $queueName;
    }

    public function pull() : \Generator
    {
        $queue = $this->context->createQueue($this->queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        $this->context->declareQueue($queue);

        $consumer = $this->context->createConsumer($queue);

        while (true) {
            if (null === ($message = $consumer->receive(60))) {
                continue;
            }

            try {
                $message = $this->serializer->deserialize(
                    $message->getBody(),
                    $message->getHeader('class'),
                    'json'
                );

                yield new ReceivedMessage($message);

                $consumer->acknowledge($message);
            } catch (SerializerException $e) {
                $this->logger->warning('Message rejected because not been able to deserialize it.', [
                    'exception' => $e,
                ]);

                $consumer->reject($message);
            }
        }
    }

    public function modifyDeadline(string $acknowledgeIdentifier, int $seconds)
    {
        // Will not do anything.
    }
}
