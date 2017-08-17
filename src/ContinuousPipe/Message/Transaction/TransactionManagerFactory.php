<?php

namespace ContinuousPipe\Message\Transaction;

use ContinuousPipe\Message\PulledMessage;

interface TransactionManagerFactory
{
    public function forMessage(PulledMessage $message) : TransactionManager;
}
