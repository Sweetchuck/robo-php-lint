<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Helper\Dummy;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\TaskIO;
use Robo\Contract\BuilderAwareInterface;
use Robo\State\StateAwareTrait;
use Robo\TaskAccessor;
use Sweetchuck\Robo\PhpLint\PhpLintTaskLoader;

class DummyTaskBuilder implements BuilderAwareInterface, ContainerAwareInterface
{

    use TaskAccessor;
    use ContainerAwareTrait;
    use StateAwareTrait;
    use TaskIO;

    use PhpLintTaskLoader {
        taskPhpLintFiles as public;
        taskPhpLintInput as public;
    }

    public function collectionBuilder(): CollectionBuilder
    {
        return CollectionBuilder::create($this->getContainer(), null);
    }
}
