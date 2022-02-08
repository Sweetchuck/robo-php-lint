<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Robo\Collection\CollectionBuilder;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Robo\PhpLint\Task\BaseTask;

class LintInputTaskTest extends TaskTestBase
{

    /**
     * @return \Sweetchuck\Robo\PhpLint\Task\LintInputTask|\Robo\Collection\CollectionBuilder
     */
    protected function createTask(): CollectionBuilder
    {
        return $this->taskBuilder->taskPhpLintInput();
    }

    public function casesBuildCommand(): array
    {
        $phpCommand = implode(' ', [
            'php',
            '-n',
            "-d 'display_errors=STDERR'",
            "-d 'error_reporting=E_ALL'",
            "-d 'log_errors=On'",
            "-d 'error_log=/dev/null'",
            "-d 'sort_open_tag=Off'",
            "-d 'asp_tags=Off'",
            '-l',
            '1>/dev/null',
        ]);

        return [
            'empty' => [
                [],
            ],
            'nasty content' => [
                [
                    "echo -n 'a'\''-\$nasty' | $phpCommand",
                ],
                [
                    'files' => [
                        'a.php' => "a'-\$nasty",
                    ],
                ],
            ],
            'basic' => [
                [
                    "echo -n 'A' | $phpCommand",
                    '&&',
                    "echo -n 'B' | $phpCommand",
                    '&&',
                    "cat 'c.php' | $phpCommand",
                    '&&',
                    "echo -n '' | $phpCommand",
                    '&&',
                    "cat 'e.php' | $phpCommand"
                ],
                [
                    'files' => [
                        'a.php' => 'A',
                        'b.php' => [
                            'content' => 'B',
                        ],
                        'c.php' => [
                            'content' => null,
                            'command' => "cat 'c.php'"
                        ],
                        'd.php' => [
                            'content' => '',
                            'command' => "cat 'd.php'"
                        ],
                        'e.php' => [
                            'command' => "cat 'e.php'"
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesBuildCommand
     */
    public function testBuildCommand(array $expected, array $options = [])
    {
        $task = $this->createTask();
        $actual = $task
            ->setOptions($options)
            ->buildCommand();

        $this->tester->assertSame(
            count(DummyProcess::$prophecy),
            count(DummyProcess::$instances),
        );
        $this->tester->assertSame($expected, $actual);
    }
}
