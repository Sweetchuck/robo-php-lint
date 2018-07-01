<?php

namespace Sweetchuck\Robo\PhpLint\Test\Helper\RoboFiles;

use Robo\Tasks;
use Sweetchuck\Robo\PhpLint\PhpLintTaskLoader;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboStateData;

class PhpLintRoboFile extends Tasks
{
    use PhpLintTaskLoader;

    /**
     * @command php-lint:files:default
     *
     * @return \Robo\Contract\TaskInterface
     */
    public function phpLintFilesDefault(
        array $options = [
            'parallelizer' => '',
            'fileNamePatterns' => [],
        ]
    ): TaskInterface {
        $lintOptions = array_filter(array_intersect_key(
            $options,
            array_flip([
                'parallelizer',
                'fileNamePatterns',
            ])
        ));

        return $this
            ->taskPhpLintFiles($lintOptions)
            ->setOutput($this->output());
    }

    /**
     * @command php-lint:files:custom
     *
     * @return \Robo\Contract\TaskInterface
     */
    public function phpLintFilesCustom(
        array $options = [
            'parallelizer' => '',
            'fileNamePattern' => '*.php',
        ]
    ): TaskInterface {
        $fileListerCommand = sprintf(
            'for fileName in %s; do echo -n $fileName"\\0"; done',
            $options['fileNamePattern']
        );

        $lintOptions = array_intersect_key(
            $options,
            array_flip([
                'parallelizer',
                'fileNamePatterns',
            ])
        );

        return $this
            ->taskPhpLintFiles(array_filter($lintOptions))
            ->setFileListerCommand($fileListerCommand);
    }

    /**
     * @command php-lint:input:command
     *
     * @return \Robo\Contract\TaskInterface
     */
    public function phpLintInputCommand(
        array $options = [
            'workingDirectory' => '',
            'fileNamePattern' => '',
            'withOutput' => false,
        ]
    ): TaskInterface {
        $workingDirectory = $options['workingDirectory'];
        $fileNamePattern = $options['fileNamePattern'];

        $fileCollectorTask = function (RoboStateData $data) use ($workingDirectory, $fileNamePattern): int {
            $files = (new \Symfony\Component\Finder\Finder())
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
