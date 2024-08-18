<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Codeception\Attribute\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Robo\PhpLint\Parallelizer;
use Sweetchuck\Robo\PhpLint\PhpLintTaskLoader;
use Sweetchuck\Robo\PhpLint\Task\BaseTask;
use Sweetchuck\Robo\PhpLint\Task\LintFilesTask;

#[CoversClass(LintFilesTask::class)]
#[CoversClass(BaseTask::class)]
#[CoversTrait(PhpLintTaskLoader::class)]
class LintFilesTaskTest extends TaskTestBase
{

    /**
     * @return \Sweetchuck\Robo\PhpLint\Task\LintFilesTask
     */
    protected function createTaskInstance(): BaseTask
    {
        return new LintFilesTask();
    }

    /**
     * @return array<string, mixed>
     */
    public static function casesBuildCommand(): array
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
                    'parallelizer' => Parallelizer::Parallel,
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
                    'parallelizer' => Parallelizer::Xargs,
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
                    'parallelizer' => Parallelizer::Parallel,
                    'fileNamePatterns' => [
                        '*.php' => true,
                        '*.module' => true,
                        '*.ignore' => false,
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
                    'parallelizer' => Parallelizer::Parallel,
                    'fileListerCommand' => 'cat files.txt',
                ],
            ],
        ];
    }

    /**
     * @param array<string> $expected
     * @param array<string, mixed> $options
     * @phpstan-param array<robo-php-lint-process-result> $processResults
     */
    #[DataProvider('casesBuildCommand')]
    public function testBuildCommand(
        array $expected,
        array $options = [],
        array $processResults = [],
    ): void {
        foreach ($processResults as $processResult) {
            DummyProcess::$prophecy[] = $processResult;
        }

        $task = $this->createTask();

        $this->tester->assertSame(
            $expected,
            // @phpstan-ignore method.notFound
            $task->setOptions($options)->buildCommand(),
        );
    }
}
