<?php

namespace ContinuousPipe\Message\Transaction\Deadline;

interface MessageDeadlineExtender
{
    public function extend();

    public function stop();
}
