<?php

namespace ContinuousPipe\Message\GooglePubSub;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\PulledMessage;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\PubSub\Message as PubSubMessage;

class PubSubPulledMessage implements PulledMessage
{
    /**
     * @var Subscription
     */
    private $subscription;
    /**
     * @var PubSubMessage
     */
    private $pubSubMessage;
    /**
     * @var Message
     */
    private $message;

    public function __construct(Subscription $subscription, PubSubMessage $pubSubMessage, Message $message)
    {
        $this->subscription = $subscription;
        $this->pubSubMessage = $pubSubMessage;
        $this->message = $message;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function acknowledge()
    {
        $this->subscription->acknowledge($this->pubSubMessage);
    }

    public function getIdentifier(): string
    {
        return $this->pubSubMessage->id();
    }
}
