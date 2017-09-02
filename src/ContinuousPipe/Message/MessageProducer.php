<?php

namespace ContinuousPipe\Message;

interface MessageProducer
{
    /**
     * @param Message $message
     *
     * @throws MessageException
     */
    public function produce(Message $message);
}
