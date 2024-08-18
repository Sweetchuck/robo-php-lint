<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint\Tests\Helper\RoboFiles;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Help;
use Robo\Tasks;
use Sweetchuck\Robo\PhpLint\PhpLintTaskLoader;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboStateData;
use Symfony\Component\Finder\Finder;

class PhpLintRoboFile extends Tasks
{
    use PhpLintTaskLoader;

    /**
     * {@inheritdoc}
     */
    protected function output()
    {
        return $this->getContainer()->get('output');
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[Command(name: 'php-lint:files:default')]
    #[Help(
        description: 'Lint files',
    )]
    public function cmdPhpLintFilesDefaultExecute(
        array $options = [
            'parallelizer' => '',
            'fileNamePatterns' => [],
        ],
    ): TaskInterface {
        $lintOptions = array_filter(array_intersect_key(
            $options,
            array_flip([
                'parallelizer',
                'fileNamePatterns',
            ])
        ));

        return $this->taskPhpLintFiles($lintOptions);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[Command(name: 'php-lint:files:custom')]
    #[Help(
        description: 'Lint files with custom file finder command',
    )]
    public function phpLintFilesCustom(
        array $options = [
            'parallelizer' => '',
            'fileNamePattern' => '*.php',
        ],
    ): TaskInterface {
        $fileListerCommand = sprintf(
            "find ./tests/_data/fixtures -name %s -print0",
            escapeshellarg($options['fileNamePattern']),
        );

        $lintOptions = array_intersect_key(
            $options,
            array_flip([
                'parallelizer',
                'fileNamePattern',
            ])
        );

        // @phpstan-ignore method.notFound
        return $this
            ->taskPhpLintFiles(array_filter($lintOptions))
            ->setFileListerCommand($fileListerCommand);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[Command(name: 'php-lint:input:command')]
    #[Help(
        description: 'Lint stdOutput of a command',
    )]
    public function cmdPhpLintInputCommandExecute(
        array $options = [
            'workingDirectory' => '',
            'fileNamePattern' => '',
            'withOutput' => false,
        ],
    ): TaskInterface {
        $workingDirectory = $options['workingDirectory'];
        $fileNamePattern = $options['fileNamePattern'];

        $fileCollectorTask = function (RoboStateData $data) use ($workingDirectory, $fileNamePattern): int {
            $files = (new Finder())
                ->in($workingDirectory)
                ->name($fileNamePattern);

            $data['files'] = [];
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            foreach ($files as $file) {
                $fileName = $workingDirectory . DIRECTORY_SEPARATOR . $file->getRelativePathname();
                $data['files'][$fileName] = [
                    'fileName' => $fileName,
                    'command' => sprintf('cat %s', escapeshellarg($fileName)),
                ];
            }

            return 0;
        };

        $phpLintInputTask = $this->taskPhpLintInput();
        $phpLintInputTask->deferTaskConfiguration('setFiles', 'files');
        if (!empty($options['withOutput'])) {
            $phpLintInputTask->setOutput($this->output());
        }

        return $this
            ->collectionBuilder()
            ->addCode($fileCollectorTask)
            ->addTask($phpLintInputTask);
    }
}
