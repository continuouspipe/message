<?php

namespace ContinuousPipe\Message\Bridge\Symfony;

use ContinuousPipe\Message\Bridge\Symfony\Event\MessageProcessed;
use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DispatchEventsTransactionManager implements TransactionManager
{
    private $decoratedManager;
    private $eventDispatcher;

    public function __construct(TransactionManager $decoratedManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->decoratedManager = $decoratedManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run(PulledMessage $message, callable $callable, array $attributes = [])
    {
        try {
            $result = $this->decoratedManager->run($message, $callable, $attributes);

            $this->eventDispatcher->dispatch(Events::MESSAGE_PROCESSED, new MessageProcessed($message));

            return $result;
        } catch (\Exception $e) {
            $this->eventDispatcher->dispatch(Events::MESSAGE_PROCESSED, new MessageProcessed($message, $e));

            throw $e;
        }
    }
}
