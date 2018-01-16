<?php

namespace ContinuousPipe\Message\Bridge\Enqueue;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageException;
use ContinuousPipe\Message\MessageProducer;
use Enqueue\AmqpExt\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use JMS\Serializer\SerializerInterface;

class EnqueueMessageProducer implements MessageProducer
{
    private $serializer;
    private $context;
    private $topicName;
    private $queueName;

    public function __construct(SerializerInterface $serializer, AmqpContext $context, string $topicName, string $queueName)
    {
        $this->serializer = $serializer;
        $this->context = $context;
        $this->topicName = $topicName;
        $this->queueName = $queueName;
    }

    public function produce(Message $message)
    {
        $topic = $this->context->createTopic($this->topicName);
        $topic->addFlag(AmqpTopic::TYPE_FANOUT);

        $queue = $this->context->createQueue($this->queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        $this->context->declareQueue($queue);
        $this->context->declareTopic($topic);
        try {
            $this->context->bind(new AmqpBind($topic, $queue));
        } catch (\Interop\Queue\Exception $e) {
            throw new MessageException('Could not bind the topic with the queue, for whatever reason');
        }

        $amqpMessage = $this->context->createMessage(
            $this->serializer->serialize($message),
            [],
            ['class' => get_class($message)]
        );

        try {
            $this->context->createProducer()->send($topic, $amqpMessage);
        } catch (\Interop\Queue\Exception $e) {
            throw new MessageException('Could not produce the message', $e->getCode(), $e);
        }
    }
}
