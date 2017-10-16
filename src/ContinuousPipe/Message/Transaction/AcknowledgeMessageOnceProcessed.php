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

    public function run(PulledMessage $message, callable $callable, array $attributes = [])
    {
        try {
            $result = $this->transactionManager->run($message, $callable, $attributes);
        } catch (\Throwable $e) {
            $result = null;
        }

        if (!isset($e)) {
            $message->acknowledge();
        } elseif (!$this->throwableCatcherVoter->shouldCatchThrowable($e)) {
            $message->acknowledge();

            $this->logger->error('Could not process message, did not re-queue', [
                'exception' => $e,
            ]);
        } else {
            $this->logger->warning('An exception occurred while processing the message, will be retried.', [
                'exception' => $e,
            ]);
        }

        return $result;
    }
}
