<?php

namespace ContinuousPipe\Message\Signal;

class SignalHandler
{
    private static $selfie;

    private $signal;
    private $triggered = false;
    private $registrations = [];

    public function isTriggered() : bool
    {
        return $this->signal !== null ? $this->signal->isTriggered() : false;
    }

    public static function create(array $signals, callable $callback = null) : self
    {
        return self::get()->register($signals, $callback);
    }

    private static function get() : self
    {
        if (null === self::$selfie) {
            self::$selfie = new self();
        }

        return self::$selfie;
    }

    private function register(array $signals, callable $callback = null) : self
    {
        foreach ($signals as $signal) {
            if (!isset($this->registrations[$signal])) {
                $this->registrations[$signal] = [];
            }

            $this->registrations[$signal][] = $callback;
        }

        $this->signal = \Seld\Signal\SignalHandler::create(array_keys($this->registrations), function($signal, $signalName) {
            if (!isset(self::get()->registrations[$signalName])) {
                return;
            }

            foreach (self::get()->registrations[$signalName] as $callback) {
                if (null !== $callback) {
                    $callback($signalName, $signalName);
                }
            }
        });

        return $this;
    }
}
