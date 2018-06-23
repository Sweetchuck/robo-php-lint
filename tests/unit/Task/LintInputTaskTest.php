<?php

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Codeception\Test\Unit;
use Sweetchuck\Robo\PhpLint\Task\LintInputTask;

class LintInputTaskTest extends Unit
{
    /**
     * @var \Sweetchuck\Robo\PhpLint\Test\UnitTester
     */
    protected $tester;

    public function casesGetCommand(): array
    {
        $phpCommandDefinitions = [
            "-d 'display_errors=STDERR'",
            "-d 'error_reporting=E_ALL'",
            "-d 'log_errors=On'",
            "-d 'error_log=/dev/null'",
            "-d 'sort_open_tag=Off'",
            "-d 'asp_tags=Off'",
        ];
        $phpCommand = sprintf("php -n %s -l 1>'/dev/null'", implode(' ', $phpCommandDefinitions));

        return [
            'empty' => [
                '',
            ],
            'nasty content' => [
                "echo -n 'a'\''-\$nasty' | $phpCommand",
                [
                    'files' => [
                        'a.php' => "a'-\$nasty",
                    ],
                ],
            ],
            'basic' => [
                implode(' && ', [
                    "echo -n 'A' | $phpCommand",
                    "echo -n 'B' | $phpCommand",
                    "cat c.php | $phpCommand",
                    "echo -n '' | $phpCommand",
                    "cat e.php | $phpCommand",

                ]),
                [
                    'files' => [
                        'a.php' => 'A',
                        'b.php' => [
                            'content' => 'B',
                        ],
                        'c.php' => [
                            'content' => null,
                            'command' => 'cat c.php'
                        ],
                        'd.php' => [
                            'content' => '',
                            'command' => 'cat d.php'
                        ],
                        'e.php' => [
                            'command' => 'cat e.php'
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options = [])
    {
        /** @var \Sweetchuck\Robo\PhpLint\Task\LintInputTask $task */
        $task = $this->construct(LintInputTask::class);
        $this->tester->assertSame(
            $expected,
            $task
                ->setOptions($options)
                ->getCommand()
        );
    }
}
