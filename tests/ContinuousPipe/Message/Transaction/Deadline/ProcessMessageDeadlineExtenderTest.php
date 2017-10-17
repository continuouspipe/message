<?php

namespace ContinuousPipe\Message\Transaction\Deadline;

use ContinuousPipe\Message\DummyMessage;
use ContinuousPipe\Message\DummyPulledMessage;
use ContinuousPipe\Message\PulledMessage;
use PHPUnit\Framework\TestCase;

class ProcessMessageDeadlineExtenderTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    function test_it_throws_an_exception_if_the_process_did_not_start()
    {
        $extender = $this->getExtender(__DIR__ . '/fixtures/loop-not-existing.sh');
        $extender->extend();
    }

    function test_it_start_a_process()
    {
        $extender = $this->getExtender(null, 'test1');
        $extender->extend();

        sleep(1.5); // We wait a bit for the extend `usleep`
        $this->shouldHaveTraced('test1', '.'."\n".'.');
    }

    function test_it_will_stop_the_started_the_process()
    {
        $extender = $this->getExtender(null, 'test2');
        $extender->extend();

        sleep(1.5); // We wait a bit for the extend `usleep`

        $extender->stop();

        sleep(2);

        $this->shouldHaveTraced('test2', '.'."\n".'.');
    }

    function test_it_allows_multiple_extenders_to_run_at_the_same_time_by_default()
    {
        $extender = $this->getExtender(null, 'test3');
        $extender->extend();
        $extender->extend();
        $extender->extend();

        sleep(2);

        $this->shouldHaveTraced('test3', implode("\n", array_fill(0, 7, '.')));
    }

    function test_it_allows_to_force_unique_run_of_extender()
    {
        $extender = $this->getExtender(null, 'test4');
        $extender->extend();
        $extender->extend();

        $extender = $this->getExtender(null, 'test4', false);
        $extender->extend();

        sleep(4);

        $this->shouldHaveTraced('test4', implode("\n", array_fill(0, 5, '.')));
    }

    private function shouldHaveTraced(string $traceName, string $expectedTrace)
    {
        $trace = trim(file_get_contents(__DIR__.'/fixtures/'.$traceName.'.trace'));

        $this->assertEquals($trace, $expectedTrace);
    }

    /**
     * @return ProcessMessageDeadlineExtender
     */
    private function getExtender(string $command = null, string $connectionName = null, bool $allowMultiple = true): ProcessMessageDeadlineExtender
    {

        $extender = new ProcessMessageDeadlineExtender(
            $command ?: __DIR__ . '/fixtures/loop.sh',
            $connectionName ?: 'connectionName',
            new DummyPulledMessage(new DummyMessage(), 'message-123', 'ack-123', function () {}),
            $allowMultiple
        );

        return $extender;
    }
}
