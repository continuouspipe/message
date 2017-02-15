<?php

namespace ContinuousPipe\Message;

interface MessageProducer
{
    public function produce(Message $message);
}
