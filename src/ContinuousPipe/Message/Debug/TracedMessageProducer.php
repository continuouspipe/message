<?php

namespace ContinuousPipe\Message\Debug;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageProducer;

class TracedMessageProducer implements MessageProducer
{
    /**
     * @var Message[]
     */
    private $producedMessages = [];

    /**
     * @var MessageProducer
     */
    private $producer;

    /**
     * @param MessageProducer $producer
     */
    public function __construct(MessageProducer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function produce(Message $message)
    {
        $this->producer->produce($message);

        $this->producedMessages[] = $message;
    }

    /**
     * @return Message[]
     */
    public function getProducedMessages(): array
    {
        return $this->producedMessages;
    }
}
