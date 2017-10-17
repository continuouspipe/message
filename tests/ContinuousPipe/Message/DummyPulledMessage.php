<?php

namespace ContinuousPipe\Message;

class DummyPulledMessage implements PulledMessage
{
    private $message;
    private $identifier;
    private $ackIdentifier;
    private $ackCallback;

    public function __construct(Message $message, string $identifier, string $ackIdentifier, callable $ackCallback)
    {
        $this->message = $message;
        $this->identifier = $identifier;
        $this->ackIdentifier = $ackIdentifier;
        $this->ackCallback = $ackCallback;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function acknowledge()
    {
        $cb = $this->ackCallback;
        $cb();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getAcknowledgeIdentifier(): string
    {
        return $this->ackIdentifier;
    }
}
