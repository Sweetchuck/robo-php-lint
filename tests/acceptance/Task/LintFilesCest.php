<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpLint\Tests\AcceptanceTester;
use Sweetchuck\Robo\PhpLint\Tests\Helper\RoboFiles\PhpLintRoboFile;

class LintFilesCest extends LintCestBase
{
    public function phpLintFilesDefaultTrueParallel(AcceptanceTester $I): void
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
            'parallel --null ' . escapeshellarg("php -n $phpDefinitions -l {} 1>/dev/null"),
        ]);

        $I->assertSame($expectedStdOutput, $stdOutput, 'stdOutput');
        $I->assertStringContainsString($expectedStdError, $stdError, 'stdError');
        $I->assertSame(0, $exitCode, 'exitCode');
    }

    public function phpLintFilesDefaultTrueXargs(AcceptanceTester $I): void
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
            'xargs -0 --max-args=1 --max-procs="$(nproc)"',
            "php -n $phpDefinitions -l\n",
        ]);

        $I->assertSame($expectedStdOutput, $this->sortLines($stdOutput));
        $I->assertStringStartsWith($expectedStdError, $stdError);
        $I->assertSame(0, $exitCode);
    }

    public function phpLintFilesDefaultFalseParallel(AcceptanceTester $I): void
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
            'parallel --null ' . escapeshellarg("php -n $phpDefinitions -l {} 1>/dev/null"),
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertStringContainsString($expectedStdError, $stdError);
    }

    public function phpLintFilesDefaultFalseXargs(AcceptanceTester $I): void
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
            'xargs -0 --max-args=1 --max-procs="$(nproc)"',
            "php -n $phpDefinitions -l\n",
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $this->sortLines($stdOutput));
        $I->assertStringStartsWith($expectedStdError, $stdError);
    }

    public function phpLintFilesCustomTrueParallel(AcceptanceTester $I): void
    {
        $id = 'php-lint:files:custom:true:parallel';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:custom',
            "--fileNamePattern=true.*.php"
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 0;
        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "find ./tests/_data/fixtures -name 'true.*.php' -print0",
            '|',
            'parallel --null ' . escapeshellarg("php -n $phpDefinitions -l {} 1>/dev/null"),
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput, 'stdOutput');
        $I->assertStringContainsString($expectedStdError, $stdError, 'stdError');
    }

    public function phpLintFilesCustomTrueXargs(AcceptanceTester $I): void
    {
        $id = 'php-lint:files:custom:true:xargs';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:custom',
            "--fileNamePattern=true.*.php",
            '--parallelizer=xargs'
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 0;
        $expectedStdOutput = implode("\n", [
            'No syntax errors detected in ./tests/_data/fixtures/true.01.php',
            'No syntax errors detected in ./tests/_data/fixtures/true.02.php',
        ]);
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "find ./tests/_data/fixtures -name 'true.*.php' -print0",
            '|',
            'xargs -0 --max-args=1 --max-procs="$(nproc)"',
            "php -n $phpDefinitions -l" . PHP_EOL,
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $this->sortLines($stdOutput));
        $I->assertStringContainsString($expectedStdError, $stdError);
    }

    public function phpLintFilesCustomFalseParallel(AcceptanceTester $I): void
    {
        $id = 'php-lint:files:custom:false:parallel';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:custom',
            "--fileNamePattern=*.php"
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 2;
        $expectedStdOutput = '';
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "find ./tests/_data/fixtures -name '*.php' -print0",
            '|',
            'parallel --null ' . escapeshellarg("php -n $phpDefinitions -l {} 1>/dev/null"),
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $stdOutput);
        $I->assertStringContainsString($expectedStdError, $stdError);
    }

    public function phpLintFilesCustomFalseXargs(AcceptanceTester $I): void
    {
        $id = 'php-lint:files:custom:false:xargs';
        $I->runRoboTask(
            $id,
            PhpLintRoboFile::class,
            'php-lint:files:custom',
            "--fileNamePattern=*.php",
            '--parallelizer=xargs'
        );
        $exitCode = $I->getRoboTaskExitCode($id);
        $stdOutput = $I->getRoboTaskStdOutput($id);
        $stdError = $I->getRoboTaskStdError($id);

        $phpDefinitions = $this->getDefaultPhpDefinitions();

        $expectedExitCode = 124;
        $expectedStdOutput = implode(\PHP_EOL, [
            'Errors parsing ./tests/_data/fixtures/false.01.php',
            'Errors parsing ./tests/_data/fixtures/false.02.php',
            'No syntax errors detected in ./tests/_data/fixtures/true.01.php',
            'No syntax errors detected in ./tests/_data/fixtures/true.02.php',
        ]);
        $expectedStdError = implode(' ', [
            ' [PHP Lint files]',
            "find ./tests/_data/fixtures -name '*.php' -print0",
            '|',
            'xargs -0 --max-args=1 --max-procs="$(nproc)"',
            "php -n $phpDefinitions -l" . PHP_EOL,
        ]);

        $I->assertSame($expectedExitCode, $exitCode);
        $I->assertSame($expectedStdOutput, $this->sortLines($stdOutput));
        $I->assertStringContainsString($expectedStdError, $stdError);
    }

    protected function sortLines(string $text): string
    {
        $lines = explode(PHP_EOL, trim($text));
        sort($lines);

        return implode(PHP_EOL, $lines);
    }
}
