<?php

namespace spec\ContinuousPipe\Message\GooglePubSub;

use ContinuousPipe\Message\DummyMessage;
use ContinuousPipe\Message\GooglePubSub\PubSubMessagePuller;
use ContinuousPipe\Message\GooglePubSub\PubSubPulledMessage;
use ContinuousPipe\Message\MessageException;
use Google\Cloud\Core\Exception\DeadlineExceededException;
use Google\Cloud\Core\Exception\ServiceException;
use Google\Cloud\Core\ServiceBuilder;
use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use JMS\Serializer\SerializerInterface;
use PhpSpec\ObjectBehavior;
use Psr\Log\NullLogger;

class PubSubMessagePullerSpec extends ObjectBehavior
{
    function let(SerializerInterface $serializer, ServiceBuilder $serviceBuilder, PubSubClient $pubSubClient, Subscription $subscription)
    {
        $this->beConstructedWith(
            $serializer,
            new NullLogger(),
            'projectId',
            'keyFilePath',
            'topicName',
            'subscriptionName',
            ['requestTimeout' => 60,], // Options
            $serviceBuilder
        );

        $pubSubClient->subscription('subscriptionName')->willReturn($subscription);
        $serviceBuilder->pubsub(["projectId" => "projectId", "keyFilePath" => "keyFilePath", "requestTimeout" => 60])->willReturn($pubSubClient);
    }

    function it_pulls_and_deserialize_messages(Subscription $subscription, SerializerInterface $serializer)
    {
        $serializer->deserialize('{}', 'DummyMessage', 'json')->willReturn(new DummyMessage());
        $subscription->pull(["returnImmediately" => false, "maxMessages" => 1])->will(function() {
            yield new Message(['data' => '{}', 'attributes' => ['class' => 'DummyMessage']], []);
        });

        $this->pull()->shouldGenerateTheMessages([
            new PubSubPulledMessage($subscription->getWrappedObject(), new Message(['data' => '{}', 'attributes' => ['class' => 'DummyMessage']], []), new DummyMessage()),
        ]);
    }

    function it_throws_a_message_exception(Subscription $subscription)
    {
        $subscription->pull(["returnImmediately" => false, "maxMessages" => 1])->willThrow(
            new DeadlineExceededException('{\n  \"error\": {\n    \"code\": 504,\n    \"message\": \"The service was unable to fulfill your request. Please try again. [code=8a75]\",\n    \"status\": \"DEADLINE_EXCEEDED\"\n  }\n}', 504)
        );

        $this->pull()->shouldThrow(MessageException::class)->duringCurrent();
    }

    function it_ignores_a_timeout_by_just_stopping_yielding(Subscription $subscription)
    {
        $subscription->pull(["returnImmediately" => false, "maxMessages" => 1])->willThrow(
            new ServiceException('cURL error 28: Operation timed out after 60000 milliseconds with 0 bytes received (see http://curl.haxx.se/libcurl/c/libcurl-errors.html)', 0)
        );

        $this->pull()->shouldGenerateTheMessages([]);
    }

    public function getMatchers() : array
    {
        return [
            'generateTheMessages' => function($subject, $messages) {
                foreach ($subject as $i => $message) {
                    if ($message != $messages[$i]) {
                        return false;
                    }
                }

                return true;
            },
        ];
    }
}
