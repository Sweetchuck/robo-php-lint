<?php

namespace Sweetchuck\Robo\PhpLint\Test\Helper\Dummy;

use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;

class DummyProcessHelper extends ProcessHelper
{
    public function run(
        OutputInterface $output,
        $cmd,
        $error = null,
        callable $callback = null,
        $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE
    ) {
        return new DummyProcess($cmd);
    }
}
