<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\PulledMessage;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullAndConsumeMessageCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('continuouspipe:message:pull-and-consume');
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $puller = $this->getContainer()->get('continuouspipe.message.message_poller');
        $consumer = $this->getContainer()->get('continuouspipe.message.message_consumer');

        $output->writeln('Waiting for messages...');

        foreach ($puller->pull() as $pulledMessage) {
            /** @var PulledMessage $pulledMessage */
            $message = $pulledMessage->getMessage();

            $output->writeln(sprintf('Consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
            $consumer->consume($message);

            $output->writeln(sprintf('Acknowledging message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
            $pulledMessage->acknowledge();
            $output->writeln(sprintf('Finished consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
        }

        $output->writeln('No message left, exiting.');
    }
}
