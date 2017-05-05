<?php

namespace ContinuousPipe\Message;

interface MessageDeadlineExpirationManager
{
    public function modifyDeadline(string $messageIdentifier, int $seconds);
}
