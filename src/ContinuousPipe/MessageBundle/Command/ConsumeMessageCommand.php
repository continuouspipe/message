<?php

namespace ContinuousPipe\MessageBundle\Command;

use ContinuousPipe\Message\Command\ReceivedMessage;
use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\MessageConsumer;
use ContinuousPipe\Message\PulledMessage;
use ContinuousPipe\Message\Transaction\TransactionManager;
use ContinuousPipe\Message\Transaction\TransactionManagerFactory;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeMessageCommand extends Command
{
    /**
     * @var MessageConsumer
     */
    private $messageConsumer;

    /**
     * @var TransactionManagerFactory
     */
    private $transactionManagerFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(MessageConsumer $messageConsumer, TransactionManagerFactory $transactionManagerFactory, SerializerInterface $serializer)
    {
        parent::__construct('continuouspipe:message:consume');

        $this->messageConsumer = $messageConsumer;
        $this->transactionManagerFactory = $transactionManagerFactory;
        $this->serializer = $serializer;

        $this->addArgument('cmd', InputArgument::REQUIRED, 'The base64-encoded command');
        $this->addArgument('attributes', InputArgument::REQUIRED, 'The base64-encoded attributes');
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        if ($attributes = base64_decode($input->getArgument('attributes'))) {
            $attributes = \GuzzleHttp\json_decode($attributes, true);
        }

        if (!isset($attributes['class'])) {
            throw new \RuntimeException('Cannot guess which message is this');
        }

        $deserializedMessage = $this->serializer->deserialize(
            base64_decode($input->getArgument('cmd')),
            $attributes['class'],
            'json'
        );

        if (!$deserializedMessage instanceof Message) {
            throw new \RuntimeException(sprintf(
                'Deserialized message of type %s is not an implementation of %s',
                get_class($deserializedMessage),
                Message::class
            ));
        }

        $message = new ReceivedMessage($deserializedMessage);
        $this->transactionManagerFactory->forMessage($message)->run($message, function(PulledMessage $pulledMessage) {
            return $this->messageConsumer->consume($pulledMessage->getMessage());
        });
    }
}
