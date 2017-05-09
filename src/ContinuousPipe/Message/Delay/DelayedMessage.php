<?php

namespace ContinuousPipe\Message\Delay;

use ContinuousPipe\Message\Message;

interface DelayedMessage extends Message
{
    /**
     * Date/time at which the message have to be run.
     *
     * @return \DateTimeInterface
     */
    public function runAt() : \DateTimeInterface;
}
