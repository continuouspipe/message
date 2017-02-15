<?php

namespace ContinuousPipe\MessageBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtendMessageDeadlineCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('continuouspipe:message:extend-deadline')
            ->addArgument('message-id', InputArgument::REQUIRED)
        ;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $poller = $this->getContainer()->get('continuouspipe.message.google_pub_sub.message_poller');

        $messageIdentifier = $input->getArgument('message-id');
        $expirationExtension = 60;

        while (true) {
            $output->write(sprintf('Extending the deadline of message "%s" by %d seconds', $messageIdentifier, $expirationExtension));
            $poller->extendDeadline($messageIdentifier, $expirationExtension);

            sleep($expirationExtension - 5);
        }
    }
}
