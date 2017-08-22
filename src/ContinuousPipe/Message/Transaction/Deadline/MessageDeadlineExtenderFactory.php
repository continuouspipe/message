<?php

namespace ContinuousPipe\Message\Transaction\Deadline;

use ContinuousPipe\Message\PulledMessage;

class MessageDeadlineExtenderFactory
{
    /**
     * @var string
     */
    private $consolePath;

    /**
     * @param string $consolePath
     */
    public function __construct(string $consolePath)
    {
        $this->consolePath = $consolePath;
    }

    public function forMessage(PulledMessage $message)
    {
        return new ProcessMessageDeadlineExtender(
            $this->consolePath,
            $message
        );
    }
}
