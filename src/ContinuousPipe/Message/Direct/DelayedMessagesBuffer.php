<?php


namespace ContinuousPipe\Message\Direct;

use ContinuousPipe\Message\Delay\DelayedMessage;
use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageProducer;

class DelayedMessagesBuffer implements MessageProducer
{
    /**
     * @var DelayedMessage[]
     */
    private $delayedMessages = [];

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
        if ($message instanceof DelayedMessage) {
            $this->delayedMessages[] = $message;
        } else {
            $this->producer->produce($message);
        }
    }

    /**
     * Flush the delayed messages to the producer.
     *
     */
    public function flushDelayedMessages()
    {
        $messagesToBeFlushed = $this->delayedMessages;
        $this->delayedMessages = [];

        foreach ($messagesToBeFlushed as $message) {
            $this->producer->produce($message);
        }
    }
}
