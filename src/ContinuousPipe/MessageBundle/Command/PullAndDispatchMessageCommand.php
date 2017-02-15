<?php

namespace ContinuousPipe\MessageBundle\Command;

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
        $eventBus = $this->getContainer()->get('command_bus');

        foreach ($messages as $message) {
            $eventBus->handle($message);
        }
    }
}
