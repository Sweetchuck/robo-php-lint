<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Acceptance\Task;

class LintCestBase
{
    protected function getDefaultPhpDefinitions(): string
    {
        return implode(' ', [
            "-d 'display_errors=STDERR'",
            "-d 'error_reporting=E_ALL'",
            "-d 'log_errors=On'",
            "-d 'error_log=/dev/null'",
            "-d 'sort_open_tag=Off'",
            "-d 'asp_tags=Off'",
        ]);
    }

    protected function getFixturesDir(): string
    {
        return './tests/_data/fixtures';
    }
}
