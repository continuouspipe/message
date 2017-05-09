<?php

namespace ContinuousPipe\Message\Delay;

use ContinuousPipe\Message\MessageDeadlineExpirationManager;
use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use ContinuousPipe\TimeResolver\TimeResolver;

class ModifyDeadlineForDelayedMessages implements TransactionManager
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var MessageDeadlineExpirationManager
     */
    private $deadlineExpirationManager;

    /**
     * @var TimeResolver
     */
    private $timeResolver;

    public function __construct(TransactionManager $transactionManager, MessageDeadlineExpirationManager $deadlineExpirationManager, TimeResolver $timeResolver)
    {
        $this->transactionManager = $transactionManager;
        $this->deadlineExpirationManager = $deadlineExpirationManager;
        $this->timeResolver = $timeResolver;
    }

    public function run(PulledMessage $message, callable $callable)
    {
        $innerMessage = $message->getMessage();

        if ($innerMessage instanceof DelayedMessage) {
            $now = $this->timeResolver->resolve();
            $shouldRunInSeconds = $innerMessage->runAt()->getTimestamp() - $now->getTimestamp();

            if ($shouldRunInSeconds > 0) {
                $this->deadlineExpirationManager->modifyDeadline(
                    $message->getAcknowledgeIdentifier(),
                    $shouldRunInSeconds
                );

                return null;
            }
        }

        return $this->transactionManager->run($message, $callable);
    }
}
