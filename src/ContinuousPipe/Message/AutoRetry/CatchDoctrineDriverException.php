<?php

namespace ContinuousPipe\Message\AutoRetry;

use Doctrine\DBAL\Exception\DriverException;
use Tolerance\Operation\ExceptionCatcher\ThrowableCatcherVoter;

class CatchDoctrineDriverException implements ThrowableCatcherVoter
{
    /**
     * {@inheritdoc}
     */
    public function shouldCatchThrowable($throwable)
    {
        return $throwable instanceof DriverException;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldCatch(\Exception $e)
    {
        return $this->shouldCatchThrowable($e);
    }
}
