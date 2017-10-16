<?php

namespace spec\ContinuousPipe\Message\AutoRetry;

use ContinuousPipe\Message\AutoRetry\CatchGivenExceptions;
use ContinuousPipe\Message\Delay\DelayedMessage;
use ContinuousPipe\Message\DummyDelayedMessage;
use ContinuousPipe\Message\DummyMessage;
use ContinuousPipe\Message\Message;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Tolerance\Operation\ExceptionCatcher\ThrowableCatcherVoter;

class CatchGivenExceptionsSpec extends ObjectBehavior
{
    function it_is_a_throwable_catcher_voter()
    {
        $this->shouldImplement(ThrowableCatcherVoter::class);
    }

    function it_catches_objects()
    {
        $this->beConstructedWith([
            DummyDelayedMessage::class,
        ]);

        $this->callOnWrappedObject('shouldCatchThrowable', [new DummyDelayedMessage()])->shouldReturn(true);
        $this->callOnWrappedObject('shouldCatchThrowable', [new DummyMessage()])->shouldReturn(false);
    }

    function it_catches_interfaces()
    {
        $this->beConstructedWith([
            DelayedMessage::class,
        ]);

        $this->callOnWrappedObject('shouldCatchThrowable', [new DummyDelayedMessage()])->shouldReturn(true);
        $this->callOnWrappedObject('shouldCatchThrowable', [new DummyMessage()])->shouldReturn(false);
    }
}
