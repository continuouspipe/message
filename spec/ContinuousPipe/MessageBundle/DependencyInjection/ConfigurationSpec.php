<?php

namespace spec\ContinuousPipe\MessageBundle\DependencyInjection;

use ContinuousPipe\MessageBundle\DependencyInjection\Configuration;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConfigurationSpec extends ObjectBehavior
{
    function it_parses_a_google_pub_sub_dsn()
    {
        $this::parseDsn('gps://project_id:account_path@subscription_name/topic_name')
            ->shouldBeLike([
                'google_pub_sub' => [
                    'project_id' => 'project_id',
                    'service_account_path' => 'account_path',
                    'topic' => 'topic_name',
                    'subscription' => 'subscription_name'
                ],
            ]);
    }

    function it_parses_a_google_pub_sub_dsn_with_options()
    {
        $this::parseDsn('gps://project_id:account_path@subscription_name/topic_name?requestTimeout=60')
            ->shouldBeLike([
                'google_pub_sub' => [
                    'project_id' => 'project_id',
                    'service_account_path' => 'account_path',
                    'topic' => 'topic_name',
                    'subscription' => 'subscription_name',
                    'options' => [
                        'requestTimeout' => 60,
                    ]
                ],
            ]);
    }
}
