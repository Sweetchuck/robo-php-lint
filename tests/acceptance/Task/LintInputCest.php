<?php

namespace Sweetchuck\Robo\PhpLint\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpLint\Test\AcceptanceTester;
use Sweetchuck\Robo\PhpLint\Test\Helper\RoboFiles\PhpLintRoboFile;

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

        $cmdPattern = "cat 'tests/_data/fixtures/true.%02d.php' | php -n $phpDefinitions -l 1>/dev/null";

        $expectedExitCode = 0;
        $expectedStdOutput = '';
        $expectedStdErrorStartsWith = ' [PHP Lint input] 2 files' . PHP_EOL;
        $expectedStdErrorContains = [
            implode(PHP_EOL, [
                "  RUN  'bash' '-c' " . escapeshellarg(sprintf($cmdPattern, 1)),
                '  RES  Command ran successfully',
                '',
            ]),
            implode(PHP_EOL, [
                "  RUN  'bash' '-c' " . escapeshellarg(sprintf($cmdPattern, 2)),
                '  RES  Command ran successfully',
                '',
            ]),
        ];

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertStringStartsWith($expectedStdErrorStartsWith, $stdError);
        foreach ($expectedStdErrorContains as $expectedFragment) {
            $I->assertStringContainsString($expectedFragment, $stdError);
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
        $expectedStdErrorStartsWith = ' [PHP Lint input] 2 files' . PHP_EOL;
        $expectedStdErrorContains = [
            sprintf($pattern, 'false.01.php', 11),
            sprintf($pattern, 'false.02.php', 11),
        ];
        $expectedStdErrorEndsWith = implode(PHP_EOL, [
            ' ',
            ' [Sweetchuck\Robo\PhpLint\Task\LintInputTask]   ',
            ' [Sweetchuck\Robo\PhpLint\Task\LintInputTask]  Exit code 255 ',
            '',
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertStringStartsWith($expectedStdErrorStartsWith, $stdError);
        foreach ($expectedStdErrorContains as $expectedFragment) {
            $I->assertStringContainsString($expectedFragment, $stdError);
        }
        $I->assertStringEndsWith($expectedStdErrorEndsWith, $stdError);
    }
}
