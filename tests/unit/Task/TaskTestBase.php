<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Unit\Task;

use Codeception\Test\Unit;
use Robo\Application;
use Robo\Robo;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcessHelper;
use Symfony\Component\Console\Helper\HelperSet;

abstract class TaskTestBase extends Unit
{
    /**
     * @var \Sweetchuck\Robo\PhpLint\Test\UnitTester
     */
    protected $tester;

    /**
     * @var \Sweetchuck\Robo\PhpLint\Task\BaseTask
     */
    protected $task;

    protected $originalContainer;

    protected $container;

    // @phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    public function _before()
    {
        parent::_before();
        DummyProcess::reset();
        $this
            ->backupContainer()
            ->initContainer()
            ->initTask();
    }

    protected function _after()
    {
        $this->restoreContainer();
        parent::_after();
    }
    //phpcs:enable PSR2.Methods.MethodDeclaration.Underscore

    protected function backupContainer()
    {
        $this->originalContainer = Robo::hasContainer() ? Robo::getContainer() : null;
        if ($this->originalContainer) {
            Robo::unsetContainer();
        }

        return $this;
    }

    protected function initContainer()
    {
        $this->container = Robo::createDefaultContainer();

        $application = new Application('RoboNvmTest', '1.0.0');
        $application->setHelperSet(new HelperSet(['process' => new DummyProcessHelper()]));
        $this->container->add('application', $application);

        return $this;
    }

    protected function restoreContainer()
    {
        if ($this->originalContainer) {
            Robo::setContainer($this->originalContainer);

            return $this;
        }

        Robo::unsetContainer();

        return $this;
    }

    /**
     * @return $this
     */
    abstract protected function initTask();
}
