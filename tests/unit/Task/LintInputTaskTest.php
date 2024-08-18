<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Codeception\Attribute\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use Sweetchuck\Robo\PhpLint\PhpLintTaskLoader;
use Sweetchuck\Robo\PhpLint\Task\BaseTask;
use Sweetchuck\Robo\PhpLint\Task\LintInputTask;

#[CoversClass(LintInputTask::class)]
#[CoversClass(BaseTask::class)]
#[CoversTrait(PhpLintTaskLoader::class)]
class LintInputTaskTest extends TaskTestBase
{

    /**
     * @return \Sweetchuck\Robo\PhpLint\Task\LintInputTask
     */
    protected function createTaskInstance(): BaseTask
    {
        return new LintInputTask();
    }

    /**
     * @return array<string, mixed>
     */
    public static function casesBuildCommand(): array
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
     * @param array<string> $expected
     * @param array<string, mixed> $options
     */
    #[DataProvider('casesBuildCommand')]
    public function testBuildCommand(
        array $expected,
        array $options = [],
    ): void {
        $task = $this->createTask();
        $this->tester->assertSame(
            $expected,
            // @phpstan-ignore method.notFound
            $task->setOptions($options)->buildCommand(),
        );
    }
}
