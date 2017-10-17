<?php

namespace spec\ContinuousPipe\Message\Transaction\Deadline;

use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\Deadline\MessageDeadlineExtender;
use PhpSpec\ObjectBehavior;

class ProcessMessageDeadlineExtenderSpec extends ObjectBehavior
{
    function let(PulledMessage $pulledMessage)
    {
        $this->beConstructedWith(
            'command',
            'connectionName',
            $pulledMessage
        );
    }

    function it_is_a_message_extended()
    {
        $this->shouldImplement(MessageDeadlineExtender::class);
    }
}
