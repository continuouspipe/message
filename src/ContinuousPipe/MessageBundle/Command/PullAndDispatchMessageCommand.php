<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\PulledMessage;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullAndDispatchMessageCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('continuouspipe:message:pull-and-dispatch');
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $messages = $this->getContainer()->get('continuouspipe.message.google_pub_sub.message_poller')->pull();
        $eventBus = $this->getContainer()->get('simple_bus.asynchronous.command_bus');

        $output->writeln('Waiting for messages...');

        foreach ($messages as $pulledMessage) {
            /** @var PulledMessage $pulledMessage */
            $message = $pulledMessage->getMessage();

            $output->writeln(sprintf('Consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
            $eventBus->handle($message);

            $output->writeln(sprintf('Acknowledging message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
            $pulledMessage->acknowledge();
            $output->writeln(sprintf('Finished consuming message "%s" (%s)', get_class($message), $pulledMessage->getIdentifier()));
        }

        $output->writeln('No message left, exiting.');
    }
}
