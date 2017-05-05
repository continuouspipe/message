<?php

namespace ContinuousPipe\TimeResolver;

class NativeTimeResolver implements TimeResolver
{
    /**
     * {@inheritdoc}
     */
    public function resolve(): \DateTimeInterface
    {
        $microTime = sprintf('%01.4f', microtime(true));

        return \DateTime::createFromFormat('U.u', $microTime, new \DateTimeZone('UTC'));
    }
}
