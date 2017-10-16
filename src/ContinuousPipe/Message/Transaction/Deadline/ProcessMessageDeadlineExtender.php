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
     * @var string
     */
    private $connectionName;

    public function __construct(string $consolePath, string $connectionName, PulledMessage $pulledMessage)
    {
        $this->consolePath = $consolePath;
        $this->pulledMessage = $pulledMessage;
        $this->connectionName = $connectionName;
    }

    public function extend()
    {
        $command = sprintf(
            '%s continuouspipe:message:extend-deadline %s %s',
            $this->consolePath,
            $this->connectionName,
            $this->pulledMessage->getAcknowledgeIdentifier()
        );

        $this->process = new Process($command);
        $this->process->start();

        if (!$this->process->isRunning()) {
            throw new \RuntimeException(sprintf(
                'Extender is not running: %s',
                $this->process->getErrorOutput()
            ));
        }
    }

    public function stop()
    {
        $this->process->stop(10);
    }
}
