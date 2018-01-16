<?php

namespace ContinuousPipe\Message\Direct;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageConsumer;
use ContinuousPipe\Message\MessageProducer;
use JMS\Serializer\SerializerInterface;

class FromProducerToConsumer implements MessageProducer
{
    /**
     * @var MessageConsumer
     */
    private $messageConsumer;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param MessageConsumer     $messageConsumer
     * @param SerializerInterface $serializer
     */
    public function __construct(MessageConsumer $messageConsumer, SerializerInterface $serializer)
    {
        $this->messageConsumer = $messageConsumer;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function produce(Message $message)
    {
        $this->messageConsumer->consume($this->serializer->deserialize(
            $this->serializer->serialize($message, 'json'),
            get_class($message),
            'json'
        ));
    }
}
