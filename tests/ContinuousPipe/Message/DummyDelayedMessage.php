<?php

namespace ContinuousPipe\Message;

use ContinuousPipe\Message\Delay\DelayedMessage;

class DummyDelayedMessage implements DelayedMessage
{
    /**
     * @var \DateTimeInterface
     */
    private $runAt;

    /**
     * @param \DateTimeInterface $runAt
     */
    public function __construct(\DateTimeInterface $runAt)
    {
        $this->runAt = $runAt;
    }

    /**
     * {@inheritdoc}
     */
    public function runAt(): \DateTimeInterface
    {
        return $this->runAt;
    }
}
