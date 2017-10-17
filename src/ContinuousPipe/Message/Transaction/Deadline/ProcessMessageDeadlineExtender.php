<?php

namespace ContinuousPipe\Message\Transaction\Deadline;

use ContinuousPipe\Message\PulledMessage;
use function Google\Cloud\Dev\impl;
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

    /**
     * @var bool
     */
    private $allowMultiple;

    public function __construct(string $consolePath, string $connectionName, PulledMessage $pulledMessage, bool $allowMultiple = true)
    {
        $this->consolePath = $consolePath;
        $this->pulledMessage = $pulledMessage;
        $this->connectionName = $connectionName;
        $this->allowMultiple = $allowMultiple;
    }

    public function extend()
    {
        if (!$this->allowMultiple) {
            $this->stopExistingRunningExtenders();
        }

        $command = implode(
            ' ',
            [
                $this->getCommandPrefix(),
                $this->connectionName,
                $this->pulledMessage->getAcknowledgeIdentifier(),
            ]
        );

        $this->process = new Process($command);
        $this->process->start();

        // Wait 50ms to ensure the process is correctly started
        usleep(50 * 1000);

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

    private function getCommandPrefix() : string
    {
        return sprintf('%s continuouspipe:message:extend-deadline', $this->consolePath);
    }

    private function stopExistingRunningExtenders(bool $throwException = false)
    {
        $processList = new Process('ps aux | grep "'.$this->getCommandPrefix().'"');
        $processList->run();

        if (!$processList->isSuccessful()) {
            throw new \RuntimeException('Could not get the running processes');
        }

        foreach (explode("\n", $processList->getOutput()) as $line) {
            $processes = preg_split('/\s+/', $line);
            if (count($processes) < 2) {
                continue;
            }

            $processId = $processes[1];

            // Do not kill itself
            if (getmypid() == (int) $processId) {
                continue;
            }

            $signal = SIGKILL;
            if (function_exists('posix_kill')) {
                $ok = @posix_kill($processId, $signal);
            } elseif ($ok = proc_open(sprintf('kill -%d %d', $signal, $processId), array(2 => array('pipe', 'w')), $pipes)) {
                $ok = false === fgets($pipes[2]);
            } else {
                throw new \RuntimeException('To kill processes, your system need to have either `posix_kill` or `proc_open` functions');
            }

            if (!$ok && $throwException) {
                throw new \RuntimeException('Something went wrong when trying to kill existing extender');
            }
        }
    }
}
