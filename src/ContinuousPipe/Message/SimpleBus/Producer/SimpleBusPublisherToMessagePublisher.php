<?php

namespace ContinuousPipe\Message\SimpleBus\Producer;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageProducer;
use SimpleBus\Asynchronous\Publisher\Publisher;

class SimpleBusPublisherToMessagePublisher implements Publisher
{
    /**
     * @var MessageProducer
     */
    private $messageProducer;

    /**
     * @param MessageProducer $messageProducer
     */
    public function __construct(MessageProducer $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function publish($message)
    {
        if (!$message instanceof Message) {
            throw new \InvalidArgumentException(sprintf(
                'The message "%s" do not implement the `Message` interface',
                get_class($message)
            ));
        }

        return $this->messageProducer->produce($message);
    }
}
