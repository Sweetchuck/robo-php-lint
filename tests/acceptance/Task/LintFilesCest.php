<?php

namespace Sweetchuck\Robo\PhpLint\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpLint\Test\AcceptanceTester;
use Sweetchuck\Robo\PhpLint\Test\Helper\RoboFiles\PhpLintRoboFile;

class LintFilesCest extends LintCestBase
{
    public function phpLintFilesDefaultTrue(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:default:true';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:default',
            "--workingDirectory=$fixturesDir",
            '--fileNamePatterns=./true.*.php'
        );

        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "cd '$fixturesDir' && git ls-files -z -- './true.*.php'",
            '|',
            'parallel --null',
            "\"php -n $phpDefinitions -l {} 1>'/dev/null'",
        ]);

        $I->assertSame(0, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertContains($expectedStdError, $stdError);
    }

    public function phpLintFilesDefaultFalse(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:default:false';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:default',
            "--workingDirectory=$fixturesDir",
            '--fileNamePatterns=./false.*.php'
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 2;
        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "cd '$fixturesDir' && git ls-files -z -- './false.*.php'",
            '|',
            'parallel --null',
            "\"php -n $phpDefinitions -l {} 1>'/dev/null'",
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertContains($expectedStdError, $stdError);
    }
}
