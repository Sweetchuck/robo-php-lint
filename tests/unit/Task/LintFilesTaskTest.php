<?php

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Codeception\Test\Unit;
use Sweetchuck\Robo\PhpLint\Task\LintFilesTask;

class LintFilesTaskTest extends Unit
{
    /**
     * @var \Sweetchuck\Robo\PhpLint\Test\UnitTester
     */
    protected $tester;

    public function casesGetCommand(): array
    {
        $listFilesCommandDefault = "git ls-files -z -- '*.php'";
        $listFilesCommandFileNamePatterns = "git ls-files -z -- '*.php' '*.module' '*.install'";

        $xargsCommandDefault = "xargs -0 --max-args='1' --max-procs=\"$(nproc)\"";

        $parallelCommandDefault = "parallel --null";

        $defaultPhpCommandDefinitions = [
            "-d 'display_errors=STDERR'",
            "-d 'error_reporting=E_ALL'",
            "-d 'log_errors=On'",
            "-d 'error_log=/dev/null'",
            "-d 'sort_open_tag=Off'",
            "-d 'asp_tags=Off'",
        ];
        $defaultPhpCommand = sprintf("php -n %s -l", implode(' ', $defaultPhpCommandDefinitions));
        $defaultPhpCommandParallel = "\"$defaultPhpCommand {} 1>'/dev/null'\"";

        $methodIsShellCallableParallel = function (string $executable): bool {
            return $executable === 'parallel';
        };

        $methodIsShellCallableXargs = function (string $executable): bool {
            return $executable === 'xargs';
        };

        return [
            'default auto parallel' => [
                "$listFilesCommandDefault | $parallelCommandDefault $defaultPhpCommandParallel",
                [],
                [
                    'isShellCallable' => $methodIsShellCallableParallel,
                ],
            ],
            'default auto xargs' => [
                "$listFilesCommandDefault | $xargsCommandDefault $defaultPhpCommand",
                [],
                [
                    'isShellCallable' => $methodIsShellCallableXargs,
                ],
            ],
            'default parallel' => [
                "$listFilesCommandDefault | $parallelCommandDefault $defaultPhpCommandParallel",
                [
                    'parallelizer' => 'parallel',
                ],
            ],
            'default xargs' => [
                "$listFilesCommandDefault | $xargsCommandDefault $defaultPhpCommand",
                [
                    'parallelizer' => 'xargs',
                ],
            ],
            'default fileNamePatterns' => [
                "$listFilesCommandFileNamePatterns | $parallelCommandDefault $defaultPhpCommandParallel",
                [
                    'parallelizer' => 'parallel',
                    'fileNamePatterns' => [
                        '*.php' => true,
                        '*.module' => true,
                        '*.install' => true,
                    ],
                ],
            ],
            'fileListerCommand string' => [
                "cat files.txt | $parallelCommandDefault $defaultPhpCommandParallel",
                [
                    'parallelizer' => 'parallel',
                    'fileListerCommand' => 'cat files.txt',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options = [], array $params = [])
    {
        /** @var \Sweetchuck\Robo\PhpLint\Task\LintFilesTask $task */
        $task = $this->construct(LintFilesTask::class, [], $params);
        $this->tester->assertSame(
            $expected,
            $task
                ->setOptions($options)
                ->getCommand()
        );
    }
}
