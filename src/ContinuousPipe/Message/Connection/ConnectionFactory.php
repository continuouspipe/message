<?php

namespace ContinuousPipe\Message\Connection;

interface ConnectionFactory
{
    public function create(array $options) : Connection;
}
