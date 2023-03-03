<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Codeception\Attribute\DataProvider;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Robo\PhpLint\Task\BaseTask;
use Sweetchuck\Robo\PhpLint\Task\LintFilesTask;

/**
 * @covers \Sweetchuck\Robo\PhpLint\Task\LintFilesTask
 * @covers \Sweetchuck\Robo\PhpLint\Task\BaseTask
 * @covers \Sweetchuck\Robo\PhpLint\PhpLintTaskLoader
 */
class LintFilesTaskTest extends TaskTestBase
{

    /**
     * @return \Sweetchuck\Robo\PhpLint\Task\LintFilesTask
     */
    protected function createTaskInstance(): BaseTask
    {
        return new LintFilesTask();
    }

    public function casesBuildCommand(): array
    {
        $listFilesCommandDefault = "git ls-files -z -- '*.php'";
        $listFilesCommandFileNamePatterns = "git ls-files -z -- '*.php' '*.module' '*.install'";

        $xargsCommandDefault = "xargs -0 --max-args=1 --max-procs=\"$(nproc)\"";

        $parallelCommandDefault = 'parallel --null';

        $defaultPhpCommand = implode(' ', [
            'php',
            '-n',
            "-d 'display_errors=STDERR'",
            "-d 'error_reporting=E_ALL'",
            "-d 'log_errors=On'",
            "-d 'error_log=/dev/null'",
            "-d 'sort_open_tag=Off'",
            "-d 'asp_tags=Off'",
            '-l',
        ]);

        $defaultPhpCommandParallel = escapeshellarg($defaultPhpCommand . ' {} 1>/dev/null');

        $exitCode0 = [
            'exitCode' => 0,
            'stdOutput' => '',
            'stdError' => '',

        ];
        $exitCode1 = [
            'exitCode' => 1,
            'stdOutput' => '',
            'stdError' => '',
        ];

        return [
            'default auto parallel' => [
                [
                    $listFilesCommandDefault,
                    '|',
                    $parallelCommandDefault,
                    $defaultPhpCommandParallel,
                ],
                [],
                [
                    $exitCode0,
                    $exitCode0,
                ],
            ],
            'default auto xargs' => [
                [
                    $listFilesCommandDefault,
                    '|',
                    $xargsCommandDefault,
                    $defaultPhpCommand,
                ],
                [],
                [
                    $exitCode1,
                    $exitCode0,
                    $exitCode1,
                    $exitCode0,
                ],
            ],
            'default parallel' => [
                [
                    $listFilesCommandDefault,
                    '|',
                    $parallelCommandDefault,
                    $defaultPhpCommandParallel,
                ],
                [
                    'parallelizer' => 'parallel',
                ],
            ],
            'default xargs' => [
                [
                    $listFilesCommandDefault,
                    '|',
                    $xargsCommandDefault,
                    $defaultPhpCommand,
                ],
                [
                    'parallelizer' => 'xargs',
                ],
            ],
            'default fileNamePatterns' => [
                [
                    $listFilesCommandFileNamePatterns,
                    '|',
                    $parallelCommandDefault,
                    $defaultPhpCommandParallel,
                ],
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
                [
                    'cat files.txt',
                    '|',
                    $parallelCommandDefault,
                    $defaultPhpCommandParallel,
                ],
                [
                    'parallelizer' => 'parallel',
                    'fileListerCommand' => 'cat files.txt',
                ],
            ],
        ];
    }

    #[DataProvider('casesBuildCommand')]
    public function testBuildCommand(array $expected, array $options = [], array $processResults = [])
    {
        foreach ($processResults as $processResult) {
            DummyProcess::$prophecy[] = $processResult;
        }

        $task = $this->createTask();

        $this->tester->assertSame(
            $expected,
            $task->setOptions($options)->buildCommand(),
        );
    }
}
