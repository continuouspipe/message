<?php

namespace ContinuousPipe\TimeResolver;

interface TimeResolver
{
    public function resolve() : \DateTimeInterface;
}
