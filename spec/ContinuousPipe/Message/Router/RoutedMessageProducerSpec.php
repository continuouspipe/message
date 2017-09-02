<?php

namespace spec\ContinuousPipe\Message\Router;

use ContinuousPipe\Message\Delay\DelayedMessage;
use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageException;
use ContinuousPipe\Message\MessageProducer;
use ContinuousPipe\River\Message\OperationalMessage;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RoutedMessageProducerSpec extends ObjectBehavior
{
    function let(MessageProducer $operationalProducer, MessageProducer $delayedProducer, MessageProducer $wildcardProducer)
    {
        $this->beConstructedWith([
            DelayedMessage::class => $delayedProducer,
            OperationalMessage::class => $operationalProducer,
            '*' => $wildcardProducer,
        ]);
    }

    function it_matches_interfaces_and_call_the_correct_producer(DelayedMessage $message, MessageProducer $delayedProducer, MessageProducer $wildcardProducer)
    {
        $delayedProducer->produce($message)->shouldBeCalled();
        $wildcardProducer->produce(Argument::any())->shouldNotBeCalled();

        $this->produce($message);
    }

    function it_supports_a_wildcard(Message $message, MessageProducer $wildcardProducer)
    {
        $wildcardProducer->produce($message)->shouldBeCalled();

        $this->produce($message);
    }

    function it_throws_an_exception_if_no_producer_is_matched(Message $message)
    {
        $this->beConstructedWith([]);

        $this->shouldThrow(MessageException::class)->duringProduce($message);
    }
}
