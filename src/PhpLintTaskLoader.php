<?php

namespace Sweetchuck\Robo\PhpLint;

use League\Container\ContainerAwareInterface;
use Robo\Collection\CollectionBuilder;

trait PhpLintTaskLoader
{
    /**
     * @return \Sweetchuck\Robo\PhpLint\Task\LintFilesTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPhpLintFiles(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\PhpLint\Task\LintFilesTask $task */
        $task = $this->task(Task\LintFilesTask::class);
        $task->setOptions($options);

        if ($this instanceof ContainerAwareInterface) {
            $container = $this->getContainer();
            if ($container) {
                $task->setContainer($this->getContainer());
            }
        }

        return $task;
    }

    /**
     * @return \Sweetchuck\Robo\PhpLint\Task\LintInputTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPhpLintInput(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\PhpLint\Task\LintInputTask $task */
        $task = $this->task(Task\LintInputTask::class);
        $task->setOptions($options);

        if ($this instanceof ContainerAwareInterface) {
            $container = $this->getContainer();
            if ($container) {
                $task->setContainer($this->getContainer());
            }
        }

        return $task;
    }
}
