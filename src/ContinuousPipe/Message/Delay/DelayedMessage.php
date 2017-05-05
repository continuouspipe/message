<?php

namespace ContinuousPipe\Message\Delay;

interface DelayedMessage
{
    /**
     * Date/time at which the message have to be run.
     *
     * @return \DateTimeInterface
     */
    public function runAt() : \DateTimeInterface;
}
