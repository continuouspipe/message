<?php

namespace ContinuousPipe\Message\SimpleBus;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageConsumer;
use SimpleBus\Message\Bus\MessageBus;

class DispatchMessageToMessageBusConsumer implements MessageConsumer
{
    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @param MessageBus $messageBus
     */
    public function __construct(MessageBus $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * {@inheritdoc}
     */
    public function consume(Message $message)
    {
        $this->messageBus->handle($message);
    }
}
