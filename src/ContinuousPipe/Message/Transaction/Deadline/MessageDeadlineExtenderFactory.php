<?php

namespace ContinuousPipe\Message\Transaction\Deadline;

use ContinuousPipe\Message\PulledMessage;

class MessageDeadlineExtenderFactory
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

    public function forMessage(PulledMessage $message)
    {
        return new ProcessMessageDeadlineExtender(
            $this->getConsolePath(),
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
