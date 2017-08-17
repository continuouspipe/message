<?php

namespace ContinuousPipe\Message\Transaction;

use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use Psr\Log\LoggerInterface;
use Tolerance\Operation\ExceptionCatcher\ThrowableCatcherVoter;

final class AcknowledgeMessageOnceProcessed implements TransactionManager
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ThrowableCatcherVoter
     */
    private $throwableCatcherVoter;

    /**
     * @param TransactionManager $transactionManager
     * @param LoggerInterface $logger
     * @param ThrowableCatcherVoter $throwableCatcherVoter
     */
    public function __construct(TransactionManager $transactionManager, LoggerInterface $logger, ThrowableCatcherVoter $throwableCatcherVoter)
    {
        $this->transactionManager = $transactionManager;
        $this->logger = $logger;
        $this->throwableCatcherVoter = $throwableCatcherVoter;
    }

    public function run(PulledMessage $message, callable $callable)
    {
        try {
            $result = $this->transactionManager->run($message, $callable);
        } catch (\Throwable $e) {
            // The throwable will be handled later...
            $this->logger->warning('An exception occurred while processing the message', [
                'exception' => $e,
            ]);

            $result = null;
        }

        if (!isset($e) || !$this->throwableCatcherVoter->shouldCatchThrowable($e)) {
            $message->acknowledge();
        }

        return $result;
    }
}
