<?php

namespace Sweetchuck\Robo\PhpLint\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpLint\Test\AcceptanceTester;
use Sweetchuck\Robo\PhpLint\Test\Helper\RoboFiles\PhpLintRoboFile;

class LintFilesCest extends LintCestBase
{
    public function phpLintFilesDefaultTrueParallel(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:default:true:parallel';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:default',
            "--fileNamePatterns=$fixturesDir/true.*.php"
        );

        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "git ls-files -z -- '$fixturesDir/true.*.php'",
            '|',
            'parallel --null',
            "\"php -n $phpDefinitions -l {} 1>'/dev/null'",
        ]);

        $I->assertSame(0, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertContains($expectedStdError, $stdError);
    }

    public function phpLintFilesDefaultTrueXargs(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:default:true:xargs';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:default',
            "--fileNamePatterns=$fixturesDir/true.*.php",
            '--parallelizer=xargs'
        );

        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedStdOutput = implode(PHP_EOL, [
            'No syntax errors detected in tests/_data/fixtures/true.01.php',
            'No syntax errors detected in tests/_data/fixtures/true.02.php',
        ]);
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "git ls-files -z -- '$fixturesDir/true.*.php'",
            '|',
            'xargs -0 --max-args=\'1\' --max-procs="$(nproc)"',
            "php -n $phpDefinitions -l\n",
        ]);

        $I->assertSame(0, $exitCode);
        $I->assertSame($expectedStdOutput, $this->sortLines($stdOutput));
        $I->assertStringStartsWith($expectedStdError, $stdError);
    }

    public function phpLintFilesDefaultFalseParallel(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:default:false:parallel';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:default',
            "--fileNamePatterns=$fixturesDir/*.php"
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 2;
        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "git ls-files -z -- '$fixturesDir/*.php'",
            '|',
            'parallel --null',
            "\"php -n $phpDefinitions -l {} 1>'/dev/null'",
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertContains($expectedStdError, $stdError);
    }

    public function phpLintFilesDefaultFalseXargs(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:default:false:xargs';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:default',
            "--fileNamePatterns=$fixturesDir/*.php",
            '--parallelizer=xargs'
        );

        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 124;
        $expectedStdOutput = implode(PHP_EOL, [
            'Errors parsing tests/_data/fixtures/false.01.php',
            'Errors parsing tests/_data/fixtures/false.02.php',
            'No syntax errors detected in tests/_data/fixtures/true.01.php',
            'No syntax errors detected in tests/_data/fixtures/true.02.php',
        ]);
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "git ls-files -z -- '$fixturesDir/*.php'",
            '|',
            'xargs -0 --max-args=\'1\' --max-procs="$(nproc)"',
            "php -n $phpDefinitions -l\n",
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $this->sortLines($stdOutput));
        $I->assertStringStartsWith($expectedStdError, $stdError);
    }

    public function phpLintFilesCustomTrueParallel(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:custom:true:parallel';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:custom',
            "--fileNamePattern=$fixturesDir/true.*.php"
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 1;
        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "for fileName in $fixturesDir/true.*.php; do echo -n \$fileName\"\\0\"; done",
            '|',
            'parallel --null',
            "\"php -n $phpDefinitions -l {} 1>'/dev/null'",
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertContains($expectedStdError, $stdError);
    }

    public function phpLintFilesCustomTrueXargs(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:custom:true:xargs';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:custom',
            "--fileNamePattern=$fixturesDir/true.*.php",
            '--parallelizer=xargs'
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 123;
        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "for fileName in $fixturesDir/true.*.php; do echo -n \$fileName\"\\0\"; done",
            '|',
            'xargs -0 --max-args=\'1\' --max-procs="$(nproc)"',
            "php -n $phpDefinitions -l" . PHP_EOL,
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertContains($expectedStdError, $stdError);
    }

    public function phpLintFilesCustomFalseParallel(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:custom:false:parallel';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:custom',
            "--fileNamePattern=$fixturesDir/*.php"
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 1;
        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "for fileName in $fixturesDir/*.php; do echo -n \$fileName\"\\0\"; done",
            '|',
            'parallel --null',
            "\"php -n $phpDefinitions -l {} 1>'/dev/null'",
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertContains($expectedStdError, $stdError);
    }

    public function phpLintFilesCustomFalseXargs(AcceptanceTester $I)
    {
        $fixturesDir = $this->getFixturesDir();
        $id = 'php-lint:files:custom:false:xargs';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:custom',
            "--fileNamePattern=$fixturesDir/*.php",
            '--parallelizer=xargs'
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 123;
        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "for fileName in $fixturesDir/*.php; do echo -n \$fileName\"\\0\"; done",
            '|',
            'xargs -0 --max-args=\'1\' --max-procs="$(nproc)"',
            "php -n $phpDefinitions -l" . PHP_EOL,
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertContains($expectedStdError, $stdError);
    }

    protected function sortLines(string $text): string
    {
        $lines = explode(PHP_EOL, trim($text));
        sort($lines);

        return implode(PHP_EOL, $lines);
    }
}
