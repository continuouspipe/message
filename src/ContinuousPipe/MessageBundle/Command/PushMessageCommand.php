<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\Connection\ConnectionRegistry;
use ContinuousPipe\Message\Debug\Ping\Ping;
use ContinuousPipe\Message\MessageConsumer;
use ContinuousPipe\Message\MessageProducer;
use ContinuousPipe\Message\MessagePuller;
use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use ContinuousPipe\Message\Transaction\TransactionManagerFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tolerance\Operation\ExceptionCatcher\ThrowableCatcherVoter;

class PushMessageCommand extends Command
{
    private $connectionRegistry;

    public function __construct(
        ConnectionRegistry $connectionRegistry
    ) {
        parent::__construct('continuouspipe:message:push');

        $this->connectionRegistry = $connectionRegistry;
    }

    protected function configure()
    {
        $this
            ->addOption('connection', 'c', InputOption::VALUE_REQUIRED, 'Name of the connection to use', null)
            ->addArgument('message-type', InputArgument::OPTIONAL, 'Type of the message (i.e. a class name)', Ping::class)
        ;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        if (null === ($connectionName = $input->getOption('connection'))) {
            throw new \InvalidArgumentException('No default connection configured. Please use the `--connection` argument to specify the connection to use');
        }

        $connection = $this->connectionRegistry->byName($connectionName);
        $messageType = $input->getArgument('message-type');

        $connection->getProducer()->produce(new $messageType());
    }
}
