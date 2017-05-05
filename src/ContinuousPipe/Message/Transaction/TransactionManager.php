<?php

namespace ContinuousPipe\Message\Transaction;

use ContinuousPipe\Message\PulledMessage;

interface TransactionManager
{
    public function run(PulledMessage $message, callable $callable);
}
