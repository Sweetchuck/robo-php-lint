<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint;

enum Parallelizer: string
{

    /**
     * Tries to detect existence of "parallel" first, then "xargs".
     */
    case Auto = 'auto';

    /**
     * Uses "parallel" to run commands parallel.
     */
    case Parallel = 'parallel';

    /**
     * Uses "xargs" to run commands parallel.
     */
    case Xargs = 'xargs';
}
