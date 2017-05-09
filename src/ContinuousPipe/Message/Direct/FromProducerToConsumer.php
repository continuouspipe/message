<?php

namespace ContinuousPipe\Message\Direct;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageConsumer;
use ContinuousPipe\Message\MessageProducer;

class FromProducerToConsumer implements MessageProducer
{
    /**
     * @var MessageConsumer
     */
    private $messageConsumer;

    /**
     * @param MessageConsumer $messageConsumer
     */
    public function __construct(MessageConsumer $messageConsumer)
    {
        $this->messageConsumer = $messageConsumer;
    }

    /**
     * {@inheritdoc}
     */
    public function produce(Message $message)
    {
        $this->messageConsumer->consume($message);
    }
}
