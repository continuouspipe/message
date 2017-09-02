<?php

namespace ContinuousPipe\Message\Router;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageException;
use ContinuousPipe\Message\MessageProducer;

class RoutedMessageProducer implements MessageProducer
{
    /**
     * @var array<string,MessageProducer>
     */
    private $messageToProducerMapping;

    /**
     * @param array<string,MessageProducer> $messageToProducerMapping
     */
    public function __construct(array $messageToProducerMapping)
    {
        $this->messageToProducerMapping = $messageToProducerMapping;
    }

    public function produce(Message $message)
    {
        foreach ($this->messageToProducerMapping as $messageType => $producer) {
            if ($this->messageMatches($message, $messageType)) {
                return $producer->produce($message);
            }
        }

        throw new MessageException(sprintf(
            'Message of type "%s" did not match any producer',
            get_class($message)
        ));
    }

    private function messageMatches(Message $message, string $messageType) : bool
    {
        return is_a($message, $messageType)
            || in_array($messageType, class_implements($message))
            || $messageType == '*';
    }
}
