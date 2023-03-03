<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Task;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\OutputAwareTrait;
use Robo\Contract\CommandInterface;
use Robo\Result;
use Robo\Task\BaseTask as RoboBaseTask;
use Robo\TaskInfo;
use Sweetchuck\Robo\PhpLint\Utils as PhpLintUtils;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Process\Process;

abstract class BaseTask extends RoboBaseTask implements CommandInterface, ContainerAwareInterface, OutputAwareInterface
{
    use ContainerAwareTrait;
    use OutputAwareTrait;

    protected string $taskName = 'PHP lint';

    protected string $shell = 'bash';

    protected string $command = '';

    protected int $processExitCode = 0;

    protected string $processStdOutput = '';

    protected string $processStdError = '';

    protected ?\Closure $processRunCallbackWrapper;

    protected array $assets = [];

    // region Options

    // region Option - workingDirectory.
    protected string $workingDirectory = '';

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function setWorkingDirectory(string $value): static
    {
        $this->workingDirectory = $value;

        return $this;
    }
    // endregion

    // region Option - phpExecutable.
    protected string $phpExecutable = 'php';

    public function getPhpExecutable(): string
    {
        return $this->phpExecutable;
    }

    public function setPhpExecutable(string $value): static
    {
        $this->phpExecutable = $value;

        return $this;
    }
    // endregion

    // region Option - assetNamePrefix.
    protected string $assetNamePrefix = '';

    public function getAssetNamePrefix(): string
    {
        return $this->assetNamePrefix;
    }

    public function setAssetNamePrefix(string $value): static
    {
        $this->assetNamePrefix = $value;

        return $this;
    }
    // endregion
    // endregion

    public function __construct()
    {
        $this->initProcessRunCallbackWrapper();
    }

    protected function initProcessRunCallbackWrapper(): static
    {
        $this->processRunCallbackWrapper = function ($type, $data) {
            $this->processRunCallback($type, $data);
        };

        return $this;
    }

    public function setOptions(array $options): static
    {
        if (array_key_exists('workingDirectory', $options)) {
            $this->setWorkingDirectory($options['workingDirectory']);
        }

        if (array_key_exists('phpExecutable', $options)) {
            $this->setPhpExecutable($options['phpExecutable']);
        }

        if (array_key_exists('assetNamePrefix', $options)) {
            $this->setAssetNamePrefix($options['assetNamePrefix']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand()
    {
        return implode(' ', $this->buildCommand());
    }

    abstract public function buildCommand(): array;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->command = $this->getCommand();

        return $this
            ->runInit()
            ->runHeader()
            ->runDoIt()
            ->runInitAssets()
            ->runProcessOutputs()
            ->runReturn();
    }

    protected function runInit(): static
    {
        return $this;
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo($this->command);

        return $this;
    }

    protected function runDoIt(): static
    {
        $processHelper = $this->getProcessHelper();
        $process = $processHelper->run(
            $this->output(),
            [
                $this->shell,
                '-c',
                $this->command,
            ],
            null,
            $this->processRunCallbackWrapper
        );

        $this->processExitCode = $process->getExitCode();
        $this->processStdOutput = $process->getOutput();
        $this->processStdError = $process->getErrorOutput();

        return $this;
    }

    protected function runInitAssets(): static
    {
        $this->assets = [];

        return $this;
    }

    protected function runProcessOutputs(): static
    {
        return $this;
    }

    protected function runReturn(): Result
    {
        return new Result(
            $this,
            $this->getTaskResultCode(),
            $this->getTaskResultMessage(),
            $this->getAssetsWithPrefixedNames()
        );
    }

    protected function processRunCallback(string $type, string $data): void
    {
        switch ($type) {
            case Process::OUT:
                $this->output()->write($data);
                break;

            case Process::ERR:
                $this->printTaskError($data);
                break;
        }
    }

    protected function getTaskResultCode(): int
    {
        return $this->processExitCode;
    }

    protected function getTaskResultMessage(): string
    {
        return $this->processStdError;
    }

    protected function getAssetsWithPrefixedNames(): array
    {
        $prefix = $this->getAssetNamePrefix();
        if (!$prefix) {
            return $this->assets;
        }

        $assets = [];
        foreach ($this->assets as $key => $value) {
            $assets["{$prefix}{$key}"] = $value;
        }

        return $assets;
    }

    public function getTaskName(): string
    {
        return $this->taskName ?: TaskInfo::formatTaskName($this);
    }

    /**
     * @return string[]
     */
    protected function getPhpCommand(): string
    {
        $cmd = [
            escapeshellcmd($this->getPhpExecutable()),
            '-n',
        ];

        $definitions = PhpLintUtils::buildKeyValueStrings($this->getPhpIniDefinitions());
        foreach ($definitions as $definition) {
            $cmd[] = '-d ' . escapeshellarg($definition);
        }

        $cmd[] = '-l';

        return implode(' ', $cmd);
    }

    protected function getPhpIniDefinitions(): array
    {
        return [
            'display_errors' => 'STDERR',
            'error_reporting' => 'E_ALL',
            'log_errors' => 'On',
            'error_log' => '/dev/null',
            'sort_open_tag' => 'Off',
            'asp_tags' => 'Off',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        if (!$context) {
            $context = [];
        }

        if (empty($context['name'])) {
            $context['name'] = $this->getTaskName();
        }

        return parent::getTaskContext($context);
    }

    protected function getProcessHelper(): ProcessHelper
    {
        return $this
            ->getContainer()
            ->get('application')
            ->getHelperSet()
            ->get('process');
    }

    protected function isShellCallable(string $executable): bool
    {
        $exitCode = $this
            ->getProcessHelper()
            ->run(
                $this->output(),
                [
                    $this->shell,
                    '-c',
                    'type ' . escapeshellarg($executable),
                ]
            )
            ->getExitCode();

        return $exitCode === 0;
    }
}
