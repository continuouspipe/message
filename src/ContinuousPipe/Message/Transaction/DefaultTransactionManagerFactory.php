<?php

namespace ContinuousPipe\Message\Transaction;

use ContinuousPipe\Message\Delay\ModifyDeadlineForDelayedMessages;
use ContinuousPipe\Message\PulledMessage;

class DefaultTransactionManagerFactory implements TransactionManagerFactory
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @param TransactionManager $transactionManager
     */
    public function __construct(TransactionManager $transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function forMessage(PulledMessage $message): TransactionManager
    {
        return $this->transactionManager;
    }
}
