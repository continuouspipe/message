<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\MessageConsumer;
use ContinuousPipe\Message\MessagePuller;
use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use ContinuousPipe\Message\Transaction\TransactionManagerFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tolerance\Operation\ExceptionCatcher\ThrowableCatcherVoter;

class PullAndConsumeMessageCommand extends ContainerAwareCommand
{
    /**
     * Maximum runtime, in seconds. 30 minutes, in order to prevent any database-timeout related issue.
     */
    const MAX_RUNTIME_IN_SECS = 1800;

    /**
     * @var bool
     */
    private $shouldStop = false;

    /**
     * @var MessagePuller
     */
    private $messagePuller;

    /**
     * @var MessageConsumer
     */
    private $messageConsumer;

    /**
     * @var ThrowableCatcherVoter
     */
    private $throwableCatcherVoter;

    /**
     * @var TransactionManagerFactory
     */
    private $transactionManagerFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        MessagePuller $messagePuller,
        MessageConsumer $messageConsumer,
        ThrowableCatcherVoter $throwableCatcherVoter,
        TransactionManagerFactory $transactionManagerFactory,
        LoggerInterface $logger
    ) {
        parent::__construct('continuouspipe:message:pull-and-consume');

        $this->messagePuller = $messagePuller;
        $this->messageConsumer = $messageConsumer;
        $this->throwableCatcherVoter = $throwableCatcherVoter;
        $this->transactionManagerFactory = $transactionManagerFactory;
        $this->logger = $logger;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);

        $output->writeln('Waiting for messages...');
        $startTime = time();

        while (!$this->shouldStop) {
            $this->pullMessages($output);

            $ranMoreThanRunTime = (time() - $startTime) > self::MAX_RUNTIME_IN_SECS;
            $this->shouldStop = $this->shouldStop || $ranMoreThanRunTime;
        }

        $output->writeln('The worker has stopped (should have stopped: '.($this->shouldStop ? 'yes' : 'no').')');
    }

    public function stopCommand()
    {
        $this->shouldStop = true;
    }

    private function pullMessages(OutputInterface $output)
    {
        foreach ($this->messagePuller->pull() as $pulledMessage) {
            $this->transactionManagerFactory->forMessage($pulledMessage)->run($pulledMessage, function (PulledMessage $pulledMessage) use ($output) {
                $message = $pulledMessage->getMessage();

                $output->writeln(sprintf('Consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
                $this->messageConsumer->consume($message);
                $output->writeln(sprintf('Finished consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
            });
        }
    }
}
