<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Task;

use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

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
    protected array $fileNamePatterns = [];

    public function getFileNamePatterns(): array
    {
        return $this->fileNamePatterns;
    }

    public function setFileNamePatterns(array $value): static
    {
        $this->fileNamePatterns = $value;

        return $this;
    }
    // endregion

    // region parallelizer
    protected string $parallelizer = 'auto';

    public function getParallelizer(): string
    {
        return $this->parallelizer;
    }

    /**
     * Allowed values:
     *   - parallel: Uses "parallel" to run commands parallel.
     *   - xargs: Uses "xargs" to run commands parallel.
     *   - auto: Tries to detect existence of "parallel" first, then "xargs".
     */
    public function setParallelizer(string $value): static
    {
        $this->parallelizer = $value;

        return $this;
    }
    // endregion

    // endregion

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
            $this->setParallelizer($options['parallelizer']);
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
            if ($parallelizerCommandType === 'parallel') {
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
            $fileNamePatterns = array_keys(array_filter($fileNamePatterns, new ArrayFilterEnabled()));
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
        $parallelizerCommandType = $this->getFinalParallelizerCommandType();
        if ($parallelizerCommandType === 'xargs') {
            return $this->getParallelizerCommandXargs();
        }

        if ($parallelizerCommandType === 'parallel') {
            return $this->getParallelizerCommandParallel();
        }

        return null;
    }

    protected function getParallelizerCommandParallel(): string
    {
        return 'parallel --null';
    }

    protected function getParallelizerCommandXargs(): string
    {
        return 'xargs -0 --max-args=1 --max-procs="$(nproc)"';
    }

    protected function getFinalParallelizerCommandType(): string
    {
        $parallelizerCommandType = $this->getParallelizer();
        if ($parallelizerCommandType === 'auto') {
            $parallelizerCommandType = $this->autodetectParallelizerCommandType();
        }

        return $parallelizerCommandType;
    }

    protected function autodetectParallelizerCommandType(): string
    {
        foreach (['parallel', 'xargs'] as $parallelizer) {
            if ($this->isShellCallable($parallelizer)) {
                return $parallelizer;
            }
        }

        return 'none';
    }
}
