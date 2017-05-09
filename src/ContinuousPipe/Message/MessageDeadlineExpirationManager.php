<?php

namespace ContinuousPipe\Message;

interface MessageDeadlineExpirationManager
{
    public function modifyDeadline(string $acknowledgeIdentifier, int $seconds);
}
