<?php

namespace Sweetchuck\Robo\PhpLint\Task;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\OutputAwareTrait;
use Robo\Contract\CommandInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask as RoboBaseTask;
use Robo\TaskInfo;
use Sweetchuck\CliCmdBuilder\CliCmdBuilderInterface;
use Sweetchuck\CliCmdBuilder\CommandBuilder;
use Sweetchuck\Robo\PhpLint\Utils as PhpLintUtils;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Process\Process;

abstract class BaseTask extends RoboBaseTask implements CommandInterface, ContainerAwareInterface, OutputAwareInterface
{
    use ContainerAwareTrait;
    use OutputAwareTrait;

    /**
     * @var string
     */
    protected $taskName = 'PHP lint';

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @var int
     */
    protected $processExitCode = 0;

    /**
     * @var string
     */
    protected $processStdOutput = '';

    /**
     * @var string
     */
    protected $processStdError = '';

    /**
     * @var null|\Closure
     */
    protected $processRunCallbackWrapper;

    /**
     * @var array
     */
    protected $assets = [];

    // region Options

    // region Option - workingDirectory.
    /**
     * @var string
     */
    protected $workingDirectory = '';

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * @return $this
     */
    public function setWorkingDirectory(string $value)
    {
        $this->workingDirectory = $value;

        return $this;
    }
    // endregion

    // region Option - phpExecutable.
    /**
     * @var string
     */
    protected $phpExecutable = 'php';

    public function getPhpExecutable(): string
    {
        return $this->phpExecutable;
    }

    /**
     * @return $this
     */
    public function setPhpExecutable(string $value)
    {
        $this->phpExecutable = $value;

        return $this;
    }
    // endregion

    // region Option - assetNamePrefix.
    /**
     * @var string
     */
    protected $assetNamePrefix = '';

    public function getAssetNamePrefix(): string
    {
        return $this->assetNamePrefix;
    }

    /**
     * @return $this
     */
    public function setAssetNamePrefix(string $value)
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

    /**
     * @return $this
     */
    protected function initProcessRunCallbackWrapper()
    {
        $this->processRunCallbackWrapper = function ($type, $data) {
            $this->processRunCallback($type, $data);
        };

        return $this;
    }

    /**
     * @return $this
     */
    public function setOptions(array $options)
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
    public function getCommand() {
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

    /**
     * @return $this
     */
    protected function runInit()
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function runHeader()
    {
        $this->printTaskInfo($this->command);

        return $this;
    }

    /**
     * @return $this
     */
    protected function runDoIt()
    {
        $processHelper = $this->getProcessHelper();
        $process = $processHelper->run(
            $this->output(),
            $this->command,
            null,
            $this->processRunCallbackWrapper
        );

        $this->processExitCode = $process->getExitCode();
        $this->processStdOutput = $process->getOutput();
        $this->processStdError = $process->getErrorOutput();

        return $this;
    }

    /**
     * @return $this
     */
    protected function runInitAssets()
    {
        $this->assets = [];

        return $this;
    }

    /**
     * @return $this
     */
    protected function runProcessOutputs()
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

    protected function getPhpCommand(): CliCmdBuilderInterface
    {
        return (new CommandBuilder())
            ->setConfig(['optionSeparator' => ' '])
            ->setExecutable($this->getPhpExecutable())
            ->addOption('-n')
            ->addOption('-d', PhpLintUtils::buildKeyValueStrings($this->getPhpIniDefinitions()))
            ->addOption('-l');
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
        $command = ['type', escapeshellarg($executable)];

        $exitCode = $this
            ->getProcessHelper()
            ->run($this->output(), $command)
            ->getExitCode();

        return $exitCode === 0;
    }
}
