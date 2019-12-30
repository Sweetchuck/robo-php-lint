<?php

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Codeception\Test\Unit;
use Sweetchuck\Robo\PhpLint\Task\LintInputTask;
use Sweetchuck\Robo\PhpLint\Test\Helper\Dummy\DummyTaskBuilder;

class LintInputTaskTest extends TaskTestBase
{

    /**
     * {@inheritdoc}
     */
    protected function initTask()
    {
        $taskBuilder = new DummyTaskBuilder();
        $taskBuilder->setContainer($this->container);

        $this->task = $taskBuilder->taskPhpLintInput();

        return $this;
    }

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
        $this->tester->assertSame(
            $expected,
            $this->task
                ->setOptions($options)
                ->getCommand()
        );
    }
}
