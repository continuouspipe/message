<?php

namespace spec\ContinuousPipe\Message\GooglePubSub;

use ContinuousPipe\Message\DummyDelayedMessage;
use ContinuousPipe\Message\DummyMessage;
use ContinuousPipe\Message\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Google\Cloud\ServiceBuilder;
use JMS\Serializer\SerializerInterface;
use PhpSpec\ObjectBehavior;

class PubSubMessageProducerSpec extends ObjectBehavior
{
    function let(SerializerInterface $serializer, ServiceBuilder $serviceBuilder, PubSubClient $pubSubClient, Topic $topic)
    {
        $serviceBuilder->pubsub()->willReturn($pubSubClient);
        $pubSubClient->topic('topic')->willReturn($topic);

        $this->beConstructedWith($serializer, $serviceBuilder, 'topic');
    }

    function it_adds_the_class_attribute(Topic $topic, SerializerInterface $serializer)
    {
        $message = new DummyMessage();
        $serializer->serialize($message, 'json')->willReturn('message');

        $topic->publish([
            'data' => 'message',
            'attributes' => [
                'class' => DummyMessage::class,
            ]
        ])->shouldBeCalled();

        $this->produce($message);
    }

    function it_adds_the_delayed_until_attribute_for_delayed_messages(Topic $topic, SerializerInterface $serializer)
    {
        $message = new DummyDelayedMessage(new \DateTime('1st August 2018 00:00:00'));
        $serializer->serialize($message, 'json')->willReturn('message');

        $topic->publish([
            'data' => 'message',
            'attributes' => [
                'class' => DummyDelayedMessage::class,
                'delayed_until' => '2018-08-01T00:00:00+0000',
            ]
        ])->shouldBeCalled();

        $this->produce($message);
    }
}
