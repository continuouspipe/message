<?php

namespace spec\ContinuousPipe\Message\Transaction;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\Deadline\MessageDeadlineExtender;
use ContinuousPipe\Message\Transaction\Deadline\MessageDeadlineExtenderFactory;
use ContinuousPipe\Message\Transaction\ExtendDeadlineDuringTransaction;
use ContinuousPipe\Message\Transaction\LongRunningMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ExtendDeadlineDuringTransactionSpec extends ObjectBehavior
{
    function let(TransactionManager $transactionManager, MessageDeadlineExtenderFactory $extenderFactory, PulledMessage $pulledMessage, Message $message)
    {
        $pulledMessage->getMessage()->willReturn($message);

        $this->beConstructedWith($transactionManager, $extenderFactory);
    }

    function it_runs_the_message(TransactionManager $transactionManager, PulledMessage $pulledMessage)
    {
        $transactionManager->run($pulledMessage, Argument::any(), [])->shouldBeCalled();

        $this->run($pulledMessage, function() {}, []);
    }

    function it_extends_the_message_dealine_when_it_is_a_long_running_with_an_exception(TransactionManager $transactionManager, PulledMessage $pulledMessage, LongRunningMessage $longRunningMessage, MessageDeadlineExtenderFactory $extenderFactory, MessageDeadlineExtender $extender)
    {
        $pulledMessage->getMessage()->willReturn($longRunningMessage);

        $extenderFactory->forMessage($pulledMessage, [])->willReturn($extender);

        $transactionManager->run($pulledMessage, Argument::any(), [])->shouldBeCalled()->willThrow(new \RuntimeException('Something went wrong'));
        $extender->extend()->shouldBeCalled();
        $extender->stop()->shouldBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringRun($pulledMessage, function(){});
    }
}
