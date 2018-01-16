<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\Connection\ConnectionRegistry;
use ContinuousPipe\Message\MessageConsumer;
use ContinuousPipe\Message\MessagePuller;
use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Signal\SignalHandler;
use ContinuousPipe\Message\Transaction\TransactionManager;
use ContinuousPipe\Message\Transaction\TransactionManagerFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tolerance\Operation\ExceptionCatcher\ThrowableCatcherVoter;

class PullAndConsumeMessageCommand extends Command
{
    /**
     * Maximum runtime, in seconds. 30 minutes, in order to prevent any database-timeout related issue.
     */
    const MAX_RUNTIME_IN_SECS = 1800;

    private $connectionRegistry;
    private $messageConsumer;
    private $transactionManagerFactory;
    private $logger;
    private $connectionName;

    public function __construct(
        ConnectionRegistry $connectionRegistry,
        MessageConsumer $messageConsumer,
        TransactionManagerFactory $transactionManagerFactory,
        LoggerInterface $logger,
        string $connectionName = null
    ) {
        parent::__construct('continuouspipe:message:pull-and-consume');

        $this->connectionRegistry = $connectionRegistry;
        $this->messageConsumer = $messageConsumer;
        $this->transactionManagerFactory = $transactionManagerFactory;
        $this->logger = $logger;
        $this->connectionName = $connectionName;
    }

    protected function configure()
    {
        $this->addOption('connection', 'c', InputOption::VALUE_REQUIRED, 'Name of the connection to use', null);
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Waiting for messages...');
        $startTime = time();

        $signal = SignalHandler::create([SIGINT, SIGTERM], function ($signal, $signalName) use ($output) {
            $output->writeln(sprintf('Received %s, will stop...', $signalName));
        });

        $shouldStop = false;
        while (!$shouldStop) {
            $this->pullMessages($input, $output, $signal);

            $ranMoreThanRunTime = (time() - $startTime) > self::MAX_RUNTIME_IN_SECS;
            $shouldStop = $shouldStop || $signal->isTriggered() || $ranMoreThanRunTime;
        }

        $output->writeln('The worker has stopped (should have stopped: ' . ($shouldStop ? 'yes' : 'no') . ')');
    }

    private function pullMessages(InputInterface $input, OutputInterface $output, SignalHandler $signal)
    {
        if (null === ($connectionName = $input->getOption('connection') ?: $this->connectionName)) {
            throw new \InvalidArgumentException('No default connection configured. Please use the `--connection` argument to specify the connection to use');
        }

        $puller = $this->connectionRegistry->byName($connectionName)->getPuller();

        foreach ($puller->pull() as $pulledMessage) {
            $this->transactionManagerFactory->forMessage($pulledMessage)->run($pulledMessage, function (PulledMessage $pulledMessage) use ($output) {
                $message = $pulledMessage->getMessage();

                $output->writeln(sprintf('Consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
                $this->messageConsumer->consume($message);
                $output->writeln(sprintf('Finished consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
            }, [
                'connectionName' => $connectionName,
            ]);

            if ($signal->isTriggered()) {
                return;
            }
        }
    }
}
