<?php

namespace ContinuousPipe\Message;

/**
 * A message puller is responsible of pulling messages from a given source.
 *
 */
interface MessagePuller
{
    /**
     * The generator will return `PulledMessage` instances.
     *
     * @return \Generator
     */
    public function pull() : \Generator;
}
