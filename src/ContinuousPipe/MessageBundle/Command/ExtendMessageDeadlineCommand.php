<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\Connection\ConnectionRegistry;
use ContinuousPipe\Message\MessageDeadlineExpirationManager;
use ContinuousPipe\Message\MessagePuller;
use ContinuousPipe\Message\Signal\SignalHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtendMessageDeadlineCommand extends ContainerAwareCommand
{
    private $connectionRegistry;

    public function __construct(ConnectionRegistry $connectionRegistry)
    {
        parent::__construct('continuouspipe:message:extend-deadline');

        $this->connectionRegistry = $connectionRegistry;
    }

    public function configure()
    {
        $this
            ->addArgument('connection', InputArgument::REQUIRED)
            ->addArgument('acknowledge-id', InputArgument::REQUIRED)
        ;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $puller = $this->connectionRegistry->byName($connectionName = $input->getArgument('connection'))->getPuller();
        if (!$puller instanceof MessageDeadlineExpirationManager) {
            throw new \RuntimeException(sprintf('Puller of connection "%s" do not supports expiration management', $connectionName));
        }

        $signal = SignalHandler::create([SIGINT, SIGTERM], function ($signal, $signalName) use ($output) {
            $output->writeln(sprintf('Received %s, will stop...', $signalName));
        });

        $acknowledgeIdentifier = $input->getArgument('acknowledge-id');
        $expirationExtension = 60;

        while (!$signal->isTriggered()) {
            $output->write(sprintf('Extending the deadline of message "%s" by %d seconds', $acknowledgeIdentifier, $expirationExtension));
            $puller->modifyDeadline($acknowledgeIdentifier, $expirationExtension);

            sleep($expirationExtension - 5);
        }
    }
}
