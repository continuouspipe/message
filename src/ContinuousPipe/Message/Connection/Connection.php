<?php

namespace ContinuousPipe\Message\Connection;

use ContinuousPipe\Message\MessageProducer;
use ContinuousPipe\Message\MessagePuller;

class Connection
{
    private $puller;
    private $producer;

    public function __construct(MessagePuller $puller = null, MessageProducer $producer = null)
    {
        $this->puller = $puller;
        $this->producer = $producer;
    }

    /**
     * @return MessagePuller|null
     */
    public function getPuller()
    {
        return $this->puller;
    }

    /**
     * @return MessageProducer|null
     */
    public function getProducer()
    {
        return $this->producer;
    }
}
