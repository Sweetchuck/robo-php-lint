<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Codeception\Test\Unit;
use League\Container\Container as LeagueContainer;
use Psr\Container\ContainerInterface;
use Robo\Application;
use Robo\Collection\CollectionBuilder;
use Robo\Config\Config;
use Robo\Robo;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcessHelper;
use Sweetchuck\Robo\PhpLint\Task\BaseTask;
use Sweetchuck\Robo\PhpLint\Task\LintFilesTask;
use Sweetchuck\Robo\PhpLint\Tests\Helper\Dummy\DummyTaskBuilder;
use Sweetchuck\Robo\PhpLint\Tests\UnitTester;
use Symfony\Component\Console\Logger\ConsoleLogger;

abstract class TaskTestBase extends Unit
{
    protected UnitTester $tester;

    protected ContainerInterface $container;

    protected Config $config;

    protected CollectionBuilder $builder;

    protected DummyTaskBuilder $taskBuilder;

    public function _before()
    {
        parent::_before();

        Robo::unsetContainer();
        DummyProcess::reset();

        $this->container = new LeagueContainer();
        $application = new Application('Sweetchuck - Robo PHPLint', '1.0.0');
        $application->getHelperSet()->set(new DummyProcessHelper(), 'process');
        $this->config = new Config();
        $input = null;
        $output = new DummyOutput([
            'verbosity' => DummyOutput::VERBOSITY_DEBUG,
        ]);

        $this->container->add('container', $this->container);

        Robo::configureContainer($this->container, $application, $this->config, $input, $output);

        $this->builder = CollectionBuilder::create($this->container, null);
        $this->taskBuilder = new DummyTaskBuilder();
        $this->taskBuilder->setContainer($this->container);
        $this->taskBuilder->setBuilder($this->builder);
    }

    /**
     * @return \Sweetchuck\Robo\PhpLint\Task\BaseTask|\Robo\Collection\CollectionBuilder
     */
    protected function createTask()
    {
        $container = new LeagueContainer();
        $application = new Application('Sweetchuck - Robo PHPLint', '1.0.0');
        $application->getHelperSet()->set(new DummyProcessHelper(), 'process');
        $config = new Config();
        $output = new DummyOutput([]);
        $loggerOutput = new DummyOutput([]);
        $logger = new ConsoleLogger($loggerOutput);

        $container->add('output', $output);
        $container->add('logger', $logger);
        $container->add('config', $config);
        $container->add('application', $application);

        $task = $this->createTaskInstance();
        $task->setContainer($container);
        $task->setOutput($output);
        $task->setLogger($logger);

        return $task;
    }

    abstract protected function createTaskInstance(): BaseTask;
}
