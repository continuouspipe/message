<?php

namespace ContinuousPipe\Message\Transaction\Tracing;

use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use Psr\Log\LoggerInterface;

class TidewaysTransactionManager implements TransactionManager
{
    /**
     * @var TransactionManager
     */
    private $decoratedManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $tidewaysApiKey;

    /**
     * @var string
     */
    private $sampleRate;

    public function __construct(
        TransactionManager $decoratedManager,
        LoggerInterface $logger,
        string $tidewaysApiKey,
        int $sampleRate = 100
    ) {
        $this->decoratedManager = $decoratedManager;
        $this->logger = $logger;
        $this->tidewaysApiKey = $tidewaysApiKey;
        $this->sampleRate = $sampleRate;
    }

    public function run(PulledMessage $message, callable $callable, array $attributes = [])
    {
        $this->start($message);

        try {
            $result = $this->decoratedManager->run($message, $callable, $attributes);
        } finally {
            $this->stop();
        }

        return $result;
    }

    private function start(PulledMessage $message)
    {
        if (class_exists('Tideways\Profiler')) {
            \Tideways\Profiler::start(array(
                'api_key' => $this->tidewaysApiKey,
                'sample_rate' => $this->sampleRate,
            ));

            \Tideways\Profiler::setTransactionName($this->getMessageName($message));
        } else {
            $this->logger->error('Tideways might not be installed as the `Tideways\Profiler` class do not exists');
        }
    }

    private function stop()
    {
        if (class_exists('Tideways\Profiler')) {
            \Tideways\Profiler::stop();
        }
    }

    private function getMessageName(PulledMessage $message)
    {
        return get_class($message->getMessage());
    }
}
