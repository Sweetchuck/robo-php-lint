<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Task;

use Sweetchuck\CliCmdBuilder\CliCmdBuilderInterface;
use Symfony\Component\Process\Process;

class LintInputTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    protected $taskName = 'PHP Lint input';

    // region files
    /**
     * @var array
     */
    protected $files = [];

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

    /**
     * @var array
     */
    protected $currentFile = [];

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

    /**
     * {@inheritdoc}
     */
    public function buildCommand(): array
    {
        $commands = [];
        foreach ($this->getFiles() as $fileName => $file) {
            $commands = array_merge($commands, $this->getMainCommand($this->normalizeFile($fileName, $file)));
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
                $this->getMainCommand($this->currentFile),
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

    protected function getMainCommand(array $file): array
    {
        return [
            $this->getContentCommand($file),
            '|',
            (string) $this->getPhpCommand(),
        ];
    }

    protected function getPhpCommand(): CliCmdBuilderInterface
    {
        return parent::getPhpCommand()
            ->addComponent([
                'type' => 'redirectStdOutput',
                'value' => '/dev/null',
            ]);
    }

    protected function getContentCommand(array $file)
    {
        if (isset($file['content'])) {
            return sprintf(
                'echo -n %s',
                escapeshellarg($file['content'])
            );
        }

        return $file['command'];
    }

    /**
     * {@inheritdoc}
     */
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
