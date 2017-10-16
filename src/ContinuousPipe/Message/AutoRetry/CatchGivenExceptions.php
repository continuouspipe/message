<?php

namespace ContinuousPipe\Message\AutoRetry;

use Tolerance\Operation\ExceptionCatcher\ThrowableCatcherVoter;

class CatchGivenExceptions implements ThrowableCatcherVoter
{
    /**
     * @var string[]
     */
    private $exceptionClasses;

    public function __construct(array $exceptionClasses = [])
    {
        $this->exceptionClasses = $exceptionClasses;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldCatch(\Exception $e)
    {
        return $this->shouldCatchThrowable($e);
    }

    /**
     * {@inheritdoc}
     */
    public function shouldCatchThrowable($throwable)
    {
        foreach ($this->exceptionClasses as $class) {
            if (is_a($throwable, $class) || is_subclass_of($throwable, $class)) {
                return true;
            }
        }

        return false;
    }
}
