<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Task;

use Symfony\Component\Process\Process;

class LintInputTask extends BaseTask
{
    protected string $taskName = 'PHP Lint input';

    // region files
    protected array $files = [];

    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @return $this
     */
    public function setFiles(array $files)
    {
        $this->files = $files;

        return $this;
    }
    // endregion

    protected array $currentFile = [];

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (array_key_exists('files', $options)) {
            $this->setFiles($options['files']);
        }

        return $this;
    }

    public function buildCommand(): array
    {
        $commands = [];
        foreach ($this->getFiles() as $fileName => $file) {
            $commands[] = $this->getMainCommand($this->normalizeFile($fileName, $file));
            $commands[] = '&&';
        }
        array_pop($commands);

        return $commands;
    }

    protected function runHeader()
    {
        $this->printTaskInfo(
            '{count} files',
            [
                'count' => count($this->getFiles()),
            ]
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $output = $this->output();
        $processHelper = $this->getProcessHelper();
        foreach ($this->getFiles() as $fileName => $file) {
            $this->currentFile = $this->normalizeFile($fileName, $file);
            $process = $processHelper->run(
                $output,
                [
                    $this->shell,
                    '-c',
                    $this->getMainCommand($this->currentFile),
                ],
                null,
                $this->processRunCallbackWrapper
            );
            $this->processExitCode = max($this->processExitCode, $process->getExitCode());
        }

        return $this;
    }

    protected function normalizeFile(string $fileName, $file): array
    {
        return is_array($file) ?
            $file + ['fileName' => $fileName]
            : ['fileName' => $fileName, 'content' => $file];
    }

    protected function getMainCommand(array $file): string
    {
        return sprintf(
            '%s | %s',
            $this->getContentCommand($file),
            $this->getPhpCommand()
        );
    }

    /**
     * @inheritdoc
     */
    protected function getPhpCommand(): string
    {
        return parent::getPhpCommand() . ' 1>/dev/null';
    }

    protected function getContentCommand(array $file): string
    {
        if (isset($file['content'])) {
            return sprintf(
                'echo -n %s',
                escapeshellarg($file['content'])
            );
        }

        return $file['command'];
    }

    protected function processRunCallback(string $type, string $data): void
    {
        switch ($type) {
            case Process::OUT:
                $this->output()->write($data);
                break;

            case Process::ERR:
                $pattern = '/(?<= in )(-|Standard input code)(?= on line \d+)/';
                $data = preg_replace($pattern, $this->currentFile['fileName'], $data);
                $this->printTaskError($data);
                break;
        }
    }
}
