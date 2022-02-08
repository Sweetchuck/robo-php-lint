<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpLint\Tests\AcceptanceTester;
use Sweetchuck\Robo\PhpLint\Tests\Helper\RoboFiles\PhpLintRoboFile;

class LintInputCest extends LintCestBase
{
    public function phpLintInputCommandTrue(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:input:command:true';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:input:command',
            "--workingDirectory=$fixturesDir",
            '--fileNamePattern=true.*.php',
            '--withOutput'
        );

        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 0;
        $expectedStdOutput = '';
        $expectedStdError = [
            ' [PHP Lint input] 2 files' . PHP_EOL,
            '  RES  Command ran successfully',
        ];

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        foreach ($expectedStdError as $expectedLine) {
            $I->assertStringContainsString($expectedLine, $stdError);
        }
    }

    public function phpLintInputCommandFalse(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:input:command:false';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:input:command',
            "--workingDirectory=$fixturesDir",
            "--fileNamePattern=false.*.php"
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $pattern = " [PHP Lint input]  Parse error: syntax error, unexpected end of file in $fixturesDir/%s on line %d";

        $expectedExitCode = 255;
        $expectedStdOutput = '';
        $expectedStdError = [
            ' [PHP Lint input] 2 files' . PHP_EOL,
            sprintf($pattern, 'false.01.php', 11),
            sprintf($pattern, 'false.02.php', 11),
        ];

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        foreach ($expectedStdError as $expectedLine) {
            $I->assertStringContainsString($expectedLine, $stdError);
        }
    }
}
