<?php

namespace Sweetchuck\Robo\PhpLint\Task;

use Sweetchuck\CliCmdBuilder\CliCmdBuilderInterface;
use Sweetchuck\CliCmdBuilder\CommandBuilder;
use Sweetchuck\CliCmdBuilder\Utils;

class LintFilesTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    protected $taskName = 'PHP Lint files';

    // region Options.

    // region fileListerCommand
    /**
     * @var string|\Sweetchuck\CliCmdBuilder\CliCmdBuilderInterface
     */
    protected $fileListerCommand = '';

    /**
     * @return string|\Sweetchuck\CliCmdBuilder\CliCmdBuilderInterface
     */
    public function getFileListerCommand()
    {
        return $this->fileListerCommand;
    }

    /**
     * @param string|\Sweetchuck\CliCmdBuilder\CliCmdBuilderInterface $value
     *
     * @return $this
     */
    public function setFileListerCommand($value)
    {
        $this->fileListerCommand = $value;

        return $this;
    }
    // endregion

    // region fileNamePatterns
    /**
     * @var array
     */
    protected $fileNamePatterns = [];

    public function getFileNamePatterns(): array
    {
        return $this->fileNamePatterns;
    }

    /**
     * @return $this
     */
    public function setFileNamePatterns(array $value)
    {
        $this->fileNamePatterns = $value;

        return $this;
    }
    // endregion

    // region parallelizer
    /**
     * @var string
     */
    protected $parallelizer = 'auto';

    public function getParallelizer(): string
    {
        return $this->parallelizer;
    }

    /**
     * Allowed values:
     *   - parallel: Uses "parallel" to run commands parallel.
     *   - xargs: Uses "xargs" to run commands parallel.
     *   - auto: Tries to detect existence of "parallel" first, then "xargs".
     *
     * @return $this
     */
    public function setParallelizer(string $value)
    {
        $this->parallelizer = $value;

        return $this;
    }
    // endregion

    // endregion

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
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

    /**
     * {@inheritdoc}
     */
    public function buildCommand(): array
    {
        $fileListerCommand = $this->getFileListerCommand() ?: $this->getDefaultFileListerCommand();
        $parallelizerCommand = $this->getParallelizerCommand();
        $phpCommand = $this->getPhpCommand();

        $parallelizerCommandType = $this->getFinalParallelizerCommandType();
        $phpCommandConfigOverrides = [];
        if ($parallelizerCommandType === 'parallel') {
            $phpCommandConfigOverrides['outputType'] = 'stringSafe';
            if ($phpCommand instanceof CommandBuilder) {
                $phpCommand
                    ->addArgument('{}', 'single:safe')
                    ->addComponent(['type' => 'redirectStdOutput', 'value' => '/dev/null']);
            }
        }

        if ($parallelizerCommand) {
            return [
                (string) $fileListerCommand,
                '|',
                (string) $parallelizerCommand,
                (string) $phpCommand->build($phpCommandConfigOverrides),
            ];
        }

        return [];
    }

    protected function getDefaultFileListerCommand(): CliCmdBuilderInterface
    {
        $cmdBuilder = new CommandBuilder();
        $cmdBuilder->setExecutable('git');

        $wd = $this->getWorkingDirectory();
        if ($wd) {
            $cmdBuilder
                ->setExecutable('cd')
                ->addArgument($wd, 'single:unsafe')
                ->addArgument('&&', 'single:safe')
                ->addArgument('git', 'single:safe');
        }

        $fileNamePatterns = Utils::filterEnabled($this->getFileNamePatterns()) ?: ['*.php'];

        $cmdBuilder
            ->addArgument('ls-files', 'single:safe')
            ->addOption('-z')
            ->addComponent(['type' => 'argument:separator', 'value' => true]);

        foreach ($fileNamePatterns as $fileNamePattern) {
            $cmdBuilder->addArgument($fileNamePattern, 'single:unsafe');
        }

        return $cmdBuilder;
    }

    protected function getParallelizerCommand(): ?CliCmdBuilderInterface
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

    protected function getParallelizerCommandParallel(): CliCmdBuilderInterface
    {
        return (new CommandBuilder())
            ->setExecutable('parallel')
            ->addOption('null');
    }

    protected function getParallelizerCommandXargs(): CliCmdBuilderInterface
    {
        return (new CommandBuilder())
            ->setExecutable('xargs')
            ->addOption('-0')
            ->addOption('max-args', '1', 'value')
            ->addOption(
                'max-procs',
                (new CommandBuilder())->setExecutable('nproc'),
                'value'
            );
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
