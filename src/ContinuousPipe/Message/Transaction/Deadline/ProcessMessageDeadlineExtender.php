<?php

namespace ContinuousPipe\Message\Transaction\Deadline;

use ContinuousPipe\Message\PulledMessage;
use Symfony\Component\Process\Process;

class ProcessMessageDeadlineExtender implements MessageDeadlineExtender
{
    /**
     * @var PulledMessage
     */
    private $pulledMessage;

    /**
     * @var string
     */
    private $consolePath;

    /**
     * @var Process|null
     */
    private $process;

    /**
     * @param string $consolePath
     * @param PulledMessage $pulledMessage
     */
    public function __construct(string $consolePath, PulledMessage $pulledMessage)
    {
        $this->consolePath = $consolePath;
        $this->pulledMessage = $pulledMessage;
    }

    public function extend()
    {
        $this->process = new Process($this->consolePath.' continuouspipe:message:extend-deadline '.$this->pulledMessage->getAcknowledgeIdentifier());
        $this->process->start();
    }

    public function stop()
    {
        $this->process->stop(0);
    }
}
