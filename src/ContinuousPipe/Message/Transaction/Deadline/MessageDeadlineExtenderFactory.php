<?php

namespace ContinuousPipe\Message\Transaction\Deadline;

use ContinuousPipe\Message\PulledMessage;

interface MessageDeadlineExtenderFactory
{
    public function forMessage(PulledMessage $message, array $attributes = []);
}
