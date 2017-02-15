<?php

namespace ContinuousPipe\Message;

/**
 * A message puller is responsible of pulling messages from a given source.
 *
 */
interface MessagePuller
{
    public function pull() : \Generator;
}
