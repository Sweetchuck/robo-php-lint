<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint;

use League\Container\ContainerAwareInterface;
use Robo\Collection\CollectionBuilder;

trait PhpLintTaskLoader
{
    /**
     * @phpstan-param robo-php-lint-task-lint-files-options $options
     *
     * @return \Sweetchuck\Robo\PhpLint\Task\LintFilesTask&\Robo\Collection\CollectionBuilder
     */
    protected function taskPhpLintFiles(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\PhpLint\Task\LintFilesTask&\Robo\Collection\CollectionBuilder $task */
        $task = $this->task(Task\LintFilesTask::class);
        $task->setOptions($options);

        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        return $task;
    }

    /**
     * @phpstan-param robo-php-lint-task-lint-input-options $options
     *
     * @return \Sweetchuck\Robo\PhpLint\Task\LintInputTask&\Robo\Collection\CollectionBuilder
     */
    protected function taskPhpLintInput(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\PhpLint\Task\LintInputTask&\Robo\Collection\CollectionBuilder $task */
        $task = $this->task(Task\LintInputTask::class);
        $task->setOptions($options);

        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        return $task;
    }
}
