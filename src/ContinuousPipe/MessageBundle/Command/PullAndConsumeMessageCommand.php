<?php

namespace ContinuousPipe\MessageBundle\Command;

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

    public function configure()
    {
        $this->setName('continuouspipe:message:pull-and-consume');
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $puller = $this->getContainer()->get('continuouspipe.message.message_poller');
        $consumer = $this->getContainer()->get('continuouspipe.message.message_consumer');
        $consolePath = $this->getContainer()->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'console';

        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);

        $output->writeln('Waiting for messages...');

        while (!$this->shouldStop) {
            foreach ($puller->pull() as $pulledMessage) {
                /** @var PulledMessage $pulledMessage */
                $message = $pulledMessage->getMessage();

                $output->writeln(sprintf('Consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));

                $extenderProcess = new Process($consolePath . ' continuouspipe:message:extend-deadline ' . $pulledMessage->getAcknowledgeIdentifier());
                $extenderProcess->start();

                $consumer->consume($message);

                $output->writeln(sprintf('Acknowledging message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
                $pulledMessage->acknowledge();
                $extenderProcess->stop(0);
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
