<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Task;

use Sweetchuck\Robo\PhpLint\Parallelizer;

class LintFilesTask extends BaseTask
{
    protected string $taskName = 'PHP Lint files';

    // region Options.
    // region fileListerCommand
    protected string $fileListerCommand = '';

    /**
     * @return string
     */
    public function getFileListerCommand(): string
    {
        return $this->fileListerCommand;
    }

    /**
     * @param string $value
     */
    public function setFileListerCommand(string $value): static
    {
        $this->fileListerCommand = $value;

        return $this;
    }
    // endregion

    // region fileNamePatterns
    /**
     * @var array<string>
     */
    protected array $fileNamePatterns = [];

    /**
     * @return array<string>
     */
    public function getFileNamePatterns(): array
    {
        return $this->fileNamePatterns;
    }

    /**
     * @param array<string> $value
     */
    public function setFileNamePatterns(array $value): static
    {
        $this->fileNamePatterns = $value;

        return $this;
    }
    // endregion

    // region parallelizer
    protected Parallelizer $parallelizer = Parallelizer::Auto;

    public function getParallelizer(): Parallelizer
    {
        return $this->parallelizer;
    }

    public function setParallelizer(Parallelizer $value): static
    {
        $this->parallelizer = $value;

        return $this;
    }
    // endregion

    // endregion

    /**
     * @phpstan-param robo-php-lint-task-lint-files-options $options
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);

        if (array_key_exists('fileListerCommand', $options)) {
            $this->setFileListerCommand($options['fileListerCommand']);
        }

        if (array_key_exists('fileNamePatterns', $options)) {
            $this->setFileNamePatterns($options['fileNamePatterns']);
        }

        if (array_key_exists('parallelizer', $options)) {
            $this->setParallelizer(
                is_string($options['parallelizer'])
                    ? Parallelizer::from($options['parallelizer'])
                    : $options['parallelizer'],
            );
        }

        return $this;
    }

    public function buildCommand(): array
    {
        $fileListerCommand = $this->getFileListerCommand() ?: $this->getDefaultFileListerCommand();
        $parallelizerCommand = $this->getParallelizerCommand();
        $phpCommand = $this->getPhpCommand();

        if ($parallelizerCommand) {
            $parallelizerCommandType = $this->getFinalParallelizerCommandType();
            if ($parallelizerCommandType === Parallelizer::Parallel) {
                $phpCommand = escapeshellarg($phpCommand . ' {} 1>/dev/null');
            }

            return [
                $fileListerCommand,
                '|',
                $parallelizerCommand,
                $phpCommand
            ];
        }

        return [];
    }

    protected function getDefaultFileListerCommand(): string
    {
        $cmd = [];

        $wd = $this->getWorkingDirectory();
        if ($wd) {
            $cmd[] = 'cd';
            $cmd[] = escapeshellarg($wd);
            $cmd[] = '&&';
        }

        $cmd[] = 'git';
        $cmd[] = 'ls-files';
        $cmd[] = '-z';
        $cmd[] = '--';

        $fileNamePatterns = $this->getFileNamePatterns();
        if (gettype(reset($fileNamePatterns)) === 'boolean') {
            $fileNamePatterns = array_keys(array_filter($fileNamePatterns));
        }

        if (!$fileNamePatterns) {
            $fileNamePatterns[] = '*.php';
        }

        foreach ($fileNamePatterns as $fileNamePattern) {
            $cmd[] = escapeshellarg($fileNamePattern);
        }

        return implode(' ', $cmd);
    }

    protected function getParallelizerCommand(): ?string
    {
        return match ($this->getFinalParallelizerCommandType()) {
            Parallelizer::Parallel => $this->getParallelizerCommandParallel(),
            Parallelizer::Xargs => $this->getParallelizerCommandXargs(),
            default => null,
        };
    }

    protected function getParallelizerCommandParallel(): string
    {
        return 'parallel --null';
    }

    protected function getParallelizerCommandXargs(): string
    {
        return 'xargs -0 --max-args=1 --max-procs="$(nproc)"';
    }

    protected function getFinalParallelizerCommandType(): ?Parallelizer
    {
        $parallelizerCommandType = $this->getParallelizer();
        if ($parallelizerCommandType->value === 'auto') {
            $parallelizerCommandType = $this->autodetectParallelizerCommandType();
        }

        return $parallelizerCommandType;
    }

    protected function autodetectParallelizerCommandType(): ?Parallelizer
    {
        foreach (['parallel', 'xargs'] as $value) {
            if ($this->isShellCallable($value)) {
                return Parallelizer::from($value);
            }
        }

        return null;
    }
}
