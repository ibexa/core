<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\Command;

use Ibexa\Bundle\IO\Migration\FileListerRegistry;
use Ibexa\Bundle\IO\Migration\FileMigratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'ibexa:io:migrate-files',
    description: 'Migrates files from one IO repository to another'
)]
final class MigrateFilesCommand extends Command
{
    /** @var \Ibexa\Bundle\IO\Migration\FileListerInterface[] */
    private array $fileListers = [];

    /**
     * @param array<string, array<string, mixed>> $configuredMetadataHandlers
     * @param array<string, array<string, mixed>> $configuredBinarydataHandlers
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function __construct(
        private array $configuredMetadataHandlers,
        private array $configuredBinarydataHandlers,
        private readonly FileListerRegistry $fileListerRegistry,
        private readonly FileMigratorInterface $fileMigrator
    ) {
        if (!array_key_exists('default', $this->configuredMetadataHandlers)) {
            $this->configuredMetadataHandlers['default'] = [];
        }
        if (!array_key_exists('default', $this->configuredBinarydataHandlers)) {
            $this->configuredBinarydataHandlers['default'] = [];
        }

        foreach ($this->fileListerRegistry->getIdentifiers() as $fileListerIdentifier) {
            $this->fileListers[] = $this->fileListerRegistry->getItem($fileListerIdentifier);
        }

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Migrate from <from_metadata_handler>,<from_binarydata_handler>')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Migrate to <to_metadata_handler>,<to_binarydata_handler>')
            ->addOption('list-io-handlers', null, InputOption::VALUE_NONE, 'List available IO handlers')
            ->addOption('bulk-count', null, InputOption::VALUE_REQUIRED, 'Number of files processed at once', 100)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute a dry run')
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> migrates files from one IO repository
to another. It can for example be used to migrate local files from the default
IO configuration to a new IO configuration, such as a clustered setup.

<fg=red>Note: This command is experimental. Use with caution!</>

The <info>--from</info> and <info>--to</info> values must be specified as <info><metadata_handler>,<binarydata_handler></info>.
If <info>--from</info> is omitted, the default IO configuration will be used.
If <info>--to</info> is omitted, the first non-default IO configuration will be used.

<fg=red>The database should not be modified while the script is being executed.
You are advised to create a backup or execute a dry run before 
proceeding with the actual update.</>

Since this script can potentially run for a very long time, to avoid memory
exhaustion run it in production environment using <info>--env=prod</info> switch.

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('list-io-handlers')) {
            $this->outputConfiguredHandlers($output);

            return self::SUCCESS;
        }

        $bulkCount = (int)$input->getOption('bulk-count');
        if ($bulkCount < 1) {
            $output->writeln('The value for --bulk-count must be a positive integer.');

            return self::SUCCESS;
        }

        $output->writeln($this->getProcessedHelp());

        $fromHandlers = $input->getOption('from') ? explode(',', $input->getOption('from')) : null;
        $toHandlers = $input->getOption('to') ? explode(',', $input->getOption('to')) : null;

        if (!$fromHandlers) {
            $fromHandlers = ['default', 'default'];
        }
        if (!$toHandlers) {
            $toHandlers = [
                array_keys($this->configuredMetadataHandlers)[0],
                array_keys($this->configuredBinarydataHandlers)[0],
            ];
        }

        if (!$this->validateHandlerOptions($fromHandlers, $toHandlers, $output)) {
            return self::SUCCESS;
        }

        $output->writeln([
            "Migrating from '{$fromHandlers[0]},{$fromHandlers[1]}' to '{$toHandlers[0]},{$toHandlers[1]}'",
            '',
        ]);

        $totalCount = 0;
        foreach ($this->fileListers as $fileLister) {
            $fileLister->setIODataHandlersByIdentifiers(
                $fromHandlers[0],
                $fromHandlers[1],
                $toHandlers[0],
                $toHandlers[1]
            );

            $totalCount += $fileLister->countFiles();
        }
        $this->fileMigrator->setIODataHandlersByIdentifiers(
            $fromHandlers[0],
            $fromHandlers[1],
            $toHandlers[0],
            $toHandlers[1]
        );

        $output->writeln([
            'Total number of files to migrate: ' . $totalCount,
            'This number does not include image variations, but they will also be migrated.',
            '',
        ]);

        if ($totalCount === 0) {
            $output->writeln('Nothing to process.');

            return self::SUCCESS;
        }

        if (!$input->getOption('no-interaction')) {
            $helper = new QuestionHelper();
            $question = new ConfirmationQuestion(
                '<question>Are you sure you want to proceed?</question> ',
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Aborting.');

                return self::SUCCESS;
            }
        }

        $this->migrateFiles(
            $totalCount,
            $bulkCount,
            $input->getOption('dry-run'),
            $output
        );

        return self::SUCCESS;
    }

    /**
     * Output the configured meta/binary data handlers.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function outputConfiguredHandlers(OutputInterface $output): void
    {
        $output->writeln(
            'Configured metadata handlers: ' . implode(', ', array_keys($this->configuredMetadataHandlers))
        );
        $output->writeln(
            'Configured binary data handlers: ' . implode(', ', array_keys($this->configuredBinarydataHandlers))
        );
    }

    /**
     * Verify that the handler options have been set to meaningful values.
     *
     * @param string[] $fromHandlers
     * @param string[] $toHandlers
     */
    protected function validateHandlerOptions(
        array $fromHandlers,
        array $toHandlers,
        OutputInterface $output
    ): bool {
        foreach (['From' => $fromHandlers, 'To' => $toHandlers] as $direction => $handlers) {
            $lowerDirection = strtolower($direction);
            if (count($handlers) !== 2) {
                $output->writeln(
                    "Enter two comma-separated values for the --$lowerDirection option: " .
                    "<{$lowerDirection}_metadata_handler>,<{$lowerDirection}_binarydata_handler>"
                );

                return false;
            }

            foreach (['meta' => $handlers[0], 'binary' => $handlers[1]] as $fileDataType => $handler) {
                $handlers = array_keys(
                    $fileDataType === 'meta' ? $this->configuredMetadataHandlers : $this->configuredBinarydataHandlers
                );
                if (!in_array($handler, $handlers, true)) {
                    $output->writeln("$direction $fileDataType data handler '$handler' is not configured.");
                    $this->outputConfiguredHandlers($output);

                    return false;
                }
            }
        }

        if ($fromHandlers === $toHandlers) {
            $output->writeln('From and to handlers are the same. No action to perform.');

            return false;
        }

        return true;
    }

    /**
     * Migrate files.
     *
     * @param int|null $totalFileCount Total count of files, null if unknown
     * @param int|null $bulkCount Number of files to process in each batch
     */
    protected function migrateFiles(
        ?int $totalFileCount,
        ?int $bulkCount,
        bool $dryRun,
        OutputInterface $output
    ): void {
        $progress = new ProgressBar($output, $totalFileCount ?? 0);
        if (null !== $totalFileCount) {
            $progress->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%");
        } else {
            $progress->setFormat("%message%\n %current% [%bar%] %elapsed:6s% %memory:6s%");
        }

        $output->writeln('');
        $progress->setMessage('Starting migration...');
        $progress->start();

        $elapsedFileCount = 0;
        $timestamp = microtime(true);
        $updateFrequency = 1;

        foreach ($this->fileListers as $fileLister) {
            $pass = 0;
            $fileCount = $fileLister->countFiles();
            do {
                $offset = $pass * $bulkCount;
                $metadataList = $fileLister->loadMetadataList($bulkCount, $offset);

                foreach ($metadataList as $metadata) {
                    if (!$dryRun) {
                        $this->fileMigrator->migrateFile($metadata);
                    }

                    $progress->setMessage('Updated file ' . $metadata->id);
                    $progress->advance();
                    ++$elapsedFileCount;

                    // Magic that ensures the progressbar is updated ca. once per second
                    if (($elapsedFileCount % $updateFrequency) === 0) {
                        $newTimestamp = microtime(true);
                        if ($newTimestamp - $timestamp > 0.5 && $updateFrequency > 1) {
                            $updateFrequency = (int)($updateFrequency / 2);
                            $progress->setRedrawFrequency($updateFrequency);
                        } elseif ($newTimestamp - $timestamp < 0.1 && $updateFrequency < 10000) {
                            $updateFrequency *= 2;
                            $progress->setRedrawFrequency($updateFrequency);
                        }
                        $timestamp = $newTimestamp;
                    }
                }

                ++$pass;
            } while ($offset + $bulkCount < $fileCount);
        }

        $progress->setMessage('');
        $progress->finish();

        $output->writeln("\n\nFinished processing $elapsedFileCount files.");
        if ($totalFileCount && $totalFileCount > $elapsedFileCount) {
            $output->writeln([
                'Files that could not be migrated: ' . ($totalFileCount - $elapsedFileCount),
                '',
            ]);
        }
    }
}
