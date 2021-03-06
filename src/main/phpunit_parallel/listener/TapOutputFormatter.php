<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Output\OutputInterface;

class TapOutputFormatter extends AbstractTestListener
{
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function begin($workers, $expectedTests)
    {
        $this->output->writeln("TAP version 13");
        $this->output->writeln("1..{$expectedTests}");
    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        $ok = count($result->getErrors()) === 0 ? 'ok' : 'not ok';

        $this->output->writeln("{$ok} {$result->getId()} - {$result->getClass()}::{$result->getName()}");

        if ($result->getErrors()) {
            fwrite(STDERR, "  ---\n");

            foreach ($result->getErrors() as $error) {
                fwrite(STDERR, '  ' . preg_replace('/\r|\r\n|\n/', "\n  ", $error->getFormatted()) . "\n");
            }

            fwrite(STDERR, "  ...\n");
        }
    }
}
