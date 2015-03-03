<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerChildProcess;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class LaneOutputFormatter implements TestEventListener
{
    private $workerCount;
    private $expectedTests;
    private $executedTests = 0;
    private $startTime;
    private $errors = [];
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $output->getFormatter()->setStyle('good', new OutputFormatterStyle('black', 'green'));
        $output->getFormatter()->setStyle('warn', new OutputFormatterStyle('black', 'yellow'));
    }

    public function begin($workerCount, $testCount)
    {
        $this->workerCount = $workerCount;
        $this->expectedTests = $testCount;
        $this->startTime = microtime(true);
    }

    public function testStarted(WorkerChildProcess $worker, TestRequest $request)
    {

    }

    public function testCompleted(WorkerChildProcess $worker, TestResult $result)
    {
        $this->executedTests++;
        $message = '<good>✓</good>';

        foreach ($result->getErrors() as $error) {
            if ($error->severity == 'error') {
                $message = '<error>E</error>';
            } elseif ($error->severity == 'warning') {
                $message = '<warn>W</warn>';
            } else {
                $message = '<error>F</error>';
            }
        }

        $details = sprintf(
            '%3d%%  %5dms  %s::%s',
            ($this->executedTests / $this->expectedTests) * 100,
            $result->getElapsed() * 1000,
            $result->getClass(),
            $result->getName()
        );


        $this->writeLanes($worker->getId(), $message, $details);
    }

    public function end()
    {
        $this->output->writeln(str_repeat("-", $this->workerCount * 2 + 1));

        $this->output->writeln('');
        $this->output->writeln('');
    }

    private function writeLanes($lane, $inlane, $message) {
        for ($i = 0; $i < $this->workerCount; $i++) {
            $this->output->write('|');
            if ($lane == $i) {
                $this->output->write($inlane);
            } else {
                $this->output->write(' ');
            }
        }

        $this->output->writeln("| $message");
    }
}