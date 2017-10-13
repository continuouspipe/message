<?php

namespace ContinuousPipe\Message\Transaction;

use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\Deadline\MessageDeadlineExtenderFactory;
use ContinuousPipe\Message\Transaction\TransactionManager;
use Symfony\Component\Process\Process;

final class ExtendDeadlineDuringTransaction implements TransactionManager
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var MessageDeadlineExtenderFactory
     */
    private $deadlineExtenderFactory;

    /**
     * @param TransactionManager $transactionManager
     * @param MessageDeadlineExtenderFactory $deadlineExtenderFactory
     */
    public function __construct(TransactionManager $transactionManager, MessageDeadlineExtenderFactory $deadlineExtenderFactory)
    {
        $this->transactionManager = $transactionManager;
        $this->deadlineExtenderFactory = $deadlineExtenderFactory;
    }

    public function run(PulledMessage $message, callable $callable, array $attributes = [])
    {
        if ($message->getMessage() instanceof LongRunningMessage) {
            $extender = $this->deadlineExtenderFactory->forMessage($message, $attributes);
            $extender->extend();
        }

        try {
            return $this->transactionManager->run($message, $callable, $attributes);
        } finally {
            if (isset($extender)) {
                $extender->stop();
            }
        }
    }
}
