<?php

namespace ContinuousPipe\Message;

interface PulledMessage
{
    public function getMessage() : Message;

    public function acknowledge();

    public function getIdentifier() : string;
}
