<?php

namespace ContinuousPipe\Message\Delay;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageProducer;

class BufferizeDelayedMessages implements MessageProducer
{
    private $decoratedProducer;
    private $buffer;

    public function __construct(MessageProducer $decoratedProducer, DelayedMessagesBuffer $buffer)
    {
        $this->decoratedProducer = $decoratedProducer;
        $this->buffer = $buffer;
    }

    /**
     * {@inheritdoc}
     */
    public function produce(Message $message)
    {
        if ($message instanceof DelayedMessage) {
            $this->buffer->buffer($this->decoratedProducer, $message);
        } else {
            $this->decoratedProducer->produce($message);
        }
    }
}
