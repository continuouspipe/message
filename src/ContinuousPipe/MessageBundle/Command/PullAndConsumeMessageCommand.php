<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\MessageConsumer;
use ContinuousPipe\Message\MessagePuller;
use ContinuousPipe\Message\PulledMessage;
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        MessagePuller $messagePuller,
        MessageConsumer $messageConsumer,
        ThrowableCatcherVoter $throwableCatcherVoter,
        LoggerInterface $logger
    ) {
        parent::__construct('continuouspipe:message:pull-and-consume');

        $this->messagePuller = $messagePuller;
        $this->messageConsumer = $messageConsumer;
        $this->throwableCatcherVoter = $throwableCatcherVoter;
        $this->logger = $logger;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $consolePath = $this->getContainer()->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'console';

        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);

        $output->writeln('Waiting for messages...');

        while (!$this->shouldStop) {
            foreach ($this->messagePuller->pull() as $pulledMessage) {
                /** @var PulledMessage $pulledMessage */
                $message = $pulledMessage->getMessage();

                $output->writeln(sprintf('Consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));

                $extenderProcess = new Process($consolePath . ' continuouspipe:message:extend-deadline ' . $pulledMessage->getAcknowledgeIdentifier());
                $extenderProcess->start();

                try {
                    $this->messageConsumer->consume($message);
                } catch (\Throwable $e) {
                    // The throwable will be handled later...
                    $this->logger->warning('An exception occurred while processing the message', [
                        'exception' => $e,
                    ]);
                } finally {
                    $extenderProcess->stop(0);
                }

                if (isset($e) && $this->throwableCatcherVoter->shouldCatchThrowable($e)) {
                    $output->writeln(sprintf('Message "%s" (%s) has not been acknowledge as the exception has been cought', get_class($message), $pulledMessage->getIdentifier()));
                } else {
                    $output->writeln(sprintf('Acknowledging message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
                    $pulledMessage->acknowledge();
                }

                $output->writeln(sprintf('Finished consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
            }
        }

        $output->writeln('The worker has stopped (should have stopped: '.($this->shouldStop ? 'yes' : 'no').')');
    }

    public function stopCommand()
    {
        $this->shouldStop = true;
    }
}
