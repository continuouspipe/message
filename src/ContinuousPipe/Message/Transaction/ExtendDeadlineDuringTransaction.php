<?php

namespace ContinuousPipe\Message\Transaction;

use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use Symfony\Component\Process\Process;

final class ExtendDeadlineDuringTransaction implements TransactionManager
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var string
     */
    private $consolePath;

    /**
     * @param TransactionManager $transactionManager
     * @param string $consolePath
     */
    public function __construct(TransactionManager $transactionManager, string $consolePath)
    {
        $this->transactionManager = $transactionManager;
        $this->consolePath = $consolePath;
    }

    public function run(PulledMessage $message, callable $callable)
    {
        if ($message instanceof LongRunningMessage) {
            $extenderProcess = new Process($this->consolePath . ' continuouspipe:message:extend-deadline ' . $message->getAcknowledgeIdentifier());
            $extenderProcess->start();
        }

        try {
            return $this->transactionManager->run($message, $callable);
        } finally {
            if (isset($extenderProcess)) {
                $extenderProcess->stop(0);
            }
        }
    }
}
