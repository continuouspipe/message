<?php

namespace ContinuousPipe\Message\Bridge\Symfony\Event;

use ContinuousPipe\Message\PulledMessage;
use Symfony\Component\EventDispatcher\Event;

class MessageProcessed extends Event
{
    /**
     * @var PulledMessage
     */
    private $message;

    /**
     * @var \Exception|null
     */
    private $exception;

    public function __construct(PulledMessage $message, \Exception $exception = null)
    {
        $this->message = $message;
        $this->exception = $exception;
    }

    /**
     * @return PulledMessage
     */
    public function getMessage(): PulledMessage
    {
        return $this->message;
    }

    /**
     * @return \Exception|null
     */
    public function getException()
    {
        return $this->exception;
    }
}
