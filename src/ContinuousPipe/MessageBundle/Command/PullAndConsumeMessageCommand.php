<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\MessageConsumer;
use ContinuousPipe\Message\MessagePuller;
use ContinuousPipe\Message\PulledMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

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

    public function __construct(MessagePuller $messagePuller, MessageConsumer $messageConsumer)
    {
        parent::__construct('continuouspipe:message:pull-and-consume');

        $this->messagePuller = $messagePuller;
        $this->messageConsumer = $messageConsumer;
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

                $this->messageConsumer->consume($message);

                $extenderProcess->stop(0);
                $output->writeln(sprintf('Acknowledging message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
                $pulledMessage->acknowledge();
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
