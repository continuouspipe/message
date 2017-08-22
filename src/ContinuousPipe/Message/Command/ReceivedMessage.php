<?php

namespace ContinuousPipe\Message\Command;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\PulledMessage;

class ReceivedMessage implements PulledMessage
{
    /**
     * @var Message
     */
    private $message;

    /**
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function acknowledge()
    {
    }

    public function getIdentifier(): string
    {
        return '';
    }

    public function getAcknowledgeIdentifier(): string
    {
        return '';
    }
}
