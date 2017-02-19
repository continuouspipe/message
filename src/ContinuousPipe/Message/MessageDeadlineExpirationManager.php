<?php

namespace ContinuousPipe\Message;

interface MessageDeadlineExpirationManager
{
    public function extendDeadline(string $messageIdentifier, int $seconds);
}
