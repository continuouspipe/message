<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\MessageDeadlineExpirationManager;
use ContinuousPipe\Message\MessagePuller;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtendMessageDeadlineCommand extends ContainerAwareCommand
{
    /**
     * @var MessageDeadlineExpirationManager
     */
    private $messageDeadlineExpirationManager;

    /**
     * @var bool
     */
    private $shouldStop = false;

    public function __construct(MessageDeadlineExpirationManager $messageDeadlineExpirationManager)
    {
        parent::__construct('continuouspipe:message:extend-deadline');

        $this->messageDeadlineExpirationManager = $messageDeadlineExpirationManager;
    }

    public function configure()
    {
        $this
            ->addArgument('message-id', InputArgument::REQUIRED)
        ;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);

        $messageIdentifier = $input->getArgument('message-id');
        $expirationExtension = 60;

        while (!$this->shouldStop) {
            $output->write(sprintf('Extending the deadline of message "%s" by %d seconds', $messageIdentifier, $expirationExtension));
            $this->messageDeadlineExpirationManager->extendDeadline($messageIdentifier, $expirationExtension);

            sleep($expirationExtension - 5);
        }
    }

    public function stopCommand()
    {
        $this->shouldStop = true;
    }
}
