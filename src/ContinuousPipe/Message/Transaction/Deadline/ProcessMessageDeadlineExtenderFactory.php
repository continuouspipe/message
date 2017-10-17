<?php

namespace ContinuousPipe\Message\Transaction\Deadline;

use ContinuousPipe\Message\PulledMessage;

class ProcessMessageDeadlineExtenderFactory implements MessageDeadlineExtenderFactory
{
    /**
     * @var string[]
     */
    private $consolePaths;

    /**
     * @param string|array $consolePaths
     */
    public function __construct($consolePaths)
    {
        $this->consolePaths = !is_array($consolePaths) ? [$consolePaths] : $consolePaths;
    }

    public function forMessage(PulledMessage $message, array $attributes = [])
    {
        if (!isset($attributes['connectionName'])) {
            throw new \RuntimeException('Must have the `connectionName` attribute');
        }

        return new ProcessMessageDeadlineExtender(
            $this->getConsolePath(),
            $attributes['connectionName'],
            $message
        );
    }

    private function getConsolePath() : string
    {
        foreach ($this->consolePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException(sprintf(
            'Cannot find console\'s path. Tried: %s',
            implode(', ', $this->consolePaths)
        ));
    }
}
