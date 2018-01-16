<?php

namespace ContinuousPipe\Message\Connection;

use ContinuousPipe\Message\Direct\DelayedMessagesBuffer;
use ContinuousPipe\Message\Direct\FromProducerToConsumer;
use ContinuousPipe\Message\GooglePubSub\PubSubMessageProducer;
use ContinuousPipe\Message\GooglePubSub\PubSubMessagePuller;
use ContinuousPipe\Message\InMemory\ArrayMessagePuller;
use ContinuousPipe\Message\MessageConsumer;
use Google\Cloud\Core\ServiceBuilder;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class DsnConnectionFactory implements ConnectionFactory
{
    private $messageConsumer;
    private $serializer;
    private $logger;

    /**
     * List of the temporary files that have been written, and that can be removed
     * when this class is destructed.
     *
     * @var string[]
     */
    private $writtenTemporaryFiles;

    public function __construct(MessageConsumer $messageConsumer, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->messageConsumer = $messageConsumer;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function create(array $options) : Connection
    {
        if (!isset($options['dsn'])) {
            throw new \InvalidArgumentException('Option "dsn" is mandatory');
        }

        if (false === ($parsedDsn = parse_url($options['dsn']))) {
            throw new \InvalidArgumentException(sprintf('The DSN "%s" is not valid', $options['dsn']));
        }

        $type = $parsedDsn['scheme'];

        // Example: direct://
        if ('direct' === $type) {
            return new Connection(
                new ArrayMessagePuller(),
                new DelayedMessagesBuffer(
                    new FromProducerToConsumer(
                        $this->messageConsumer,
                        $this->serializer
                    )
                )
            );
        }

        // Example: 'gps://project_id:base64_encoded_service_account@subscription_name/topic?requestTimeout=60'
        if ('gps' === $type) {
            isset($parsedDsn['query']) ? parse_str($parsedDsn['query'], $gpsOptions) : $gpsOptions = [];

            $projectId = $parsedDsn['user'];
            $serviceAccountFile = $this->temporaryServiceAccountFile($parsedDsn['pass']);
            $subscriptionName = $parsedDsn['host'];
            $topicName = substr($parsedDsn['path'], 1);

            return new Connection(
                new PubSubMessagePuller(
                    $this->serializer,
                    $this->logger,
                    $projectId,
                    $serviceAccountFile,
                    $topicName,
                    $subscriptionName,
                    $gpsOptions
                ),
                new PubSubMessageProducer(
                    $this->serializer,
                    new ServiceBuilder([
                        'projectId' => $parsedDsn['user'],
                        'keyFilePath' => $serviceAccountFile,
                    ]),
                    $topicName
                )
            );
        }

        throw new \InvalidArgumentException(sprintf(
            'Driver type "%s" is not supported',
            $type
        ));
    }

    private function temporaryServiceAccountFile(string $base64EncodedServiceAccount)
    {
        if (false === $jsonServiceAccount = base64_decode($base64EncodedServiceAccount)) {
            throw new \InvalidArgumentException(sprintf('Service account should be base64-encoded but got: %s', $base64EncodedServiceAccount));
        }

        $serviceAccountFile = tempnam(sys_get_temp_dir(), 'sa');
        file_put_contents($serviceAccountFile, $jsonServiceAccount);

        $this->writtenTemporaryFiles[] = $serviceAccountFile;

        return $serviceAccountFile;
    }

    public function __destruct()
    {
        foreach ($this->writtenTemporaryFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
