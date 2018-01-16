<?php

namespace ContinuousPipe\Message\Delay;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageProducer;

final class DelayedMessagesBuffer
{
    private $buffer = [];

    public function buffer(MessageProducer $producer, DelayedMessage $message)
    {
        $this->buffer[] = [$producer, $message];
    }

    public function flush()
    {
        foreach ($this->buffer as $item) {
            $item[0]->produce($item[1]);
        }

        $this->buffer = [];
    }
}
