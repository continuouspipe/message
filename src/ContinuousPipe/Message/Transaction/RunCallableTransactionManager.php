<?php

namespace ContinuousPipe\Message\Transaction;

use ContinuousPipe\Message\PulledMessage;

final class RunCallableTransactionManager implements TransactionManager
{
    public function run(PulledMessage $message, callable $callable)
    {
        return $callable($message);
    }
}
