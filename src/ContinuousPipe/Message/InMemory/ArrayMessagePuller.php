<?php

namespace ContinuousPipe\Message\InMemory;

use ContinuousPipe\Message\MessagePuller;
use ContinuousPipe\Message\PulledMessage;

class ArrayMessagePuller implements MessagePuller
{
    /**
     * @var PulledMessage[]
     */
    private $messages;

    /**
     * @param PulledMessage[] $messages
     */
    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function pull(): \Generator
    {
        foreach ($this->messages as $message) {
            yield $message;
        }
    }
}
