<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException as APIInvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskedTable;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskTranslationGateway;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Backfills "always_available" and the language translation tables from the legacy language bitmask columns.
 */
#[AsCommand(
    name: 'ibexa:languages:backfill-translations',
    description: 'Backfills "always_available" and the language translation tables from the legacy language bitmask columns',
    help: <<<'EOT'
        The command <info>%command.name%</info> populates "ibexa_content_translation",
        "ibexa_content_version_translation" and "ibexa_url_alias_ml_translation" from the existing
        "language_mask"/"lang_mask" bitmask columns, and sets "always_available" on "ibexa_content"
        and "ibexa_content_version" from bit 0 of "language_mask". It is idempotent - already-backfilled rows are
        skipped - so it is safe to re-run, including after a partial/interrupted run.

        Run <info>ibexa:languages:verify-translations</info> afterward to confirm the backfill is complete.
        EOT,
)]
final class BackfillLanguageTranslationsCommand
{
    private const int DEFAULT_BATCH_SIZE = 5000;

    private const string TABLE_ALL = 'all';

    public function __construct(private readonly MaskTranslationGateway $gateway) {}

    /**
     * @throws APIInvalidArgumentException
     * @throws DBALException
     */
    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Which table to backfill: one of "content", "content_version", "url_alias", or "all" for all of them.', shortcut: 't')]
        string $table = self::TABLE_ALL,
        #[Option(description: 'Number of primary-key values processed per batch.')]
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        #[Option(description: 'Report how many rows would be inserted without writing anything.')]
        bool $dryRun = false,
    ): int {
        if ($batchSize < 1) {
            throw new InvalidArgumentException('batch-size', 'must be a positive integer.');
        }

        foreach ($this->resolveTables($table) as $maskedTable) {
            $this->backfillTable($maskedTable, $batchSize, $dryRun, $output);
        }

        return Command::SUCCESS;
    }

    /**
     * @return non-empty-list<MaskedTable>
     *
     * @throws APIInvalidArgumentException
     */
    private function resolveTables(string $table): array
    {
        if ($table === self::TABLE_ALL) {
            return MaskedTable::cases();
        }

        return [
            MaskedTable::tryFrom($table) ?? throw new InvalidArgumentException(
                'table',
                sprintf(
                    '"%s" is not one of "%s" or "%s".',
                    $table,
                    implode('", "', array_column(MaskedTable::cases(), 'value')),
                    self::TABLE_ALL
                )
            ),
        ];
    }

    /**
     * @throws APIInvalidArgumentException
     * @throws DBALException
     */
    private function backfillTable(
        MaskedTable $table,
        int $batchSize,
        bool $dryRun,
        OutputInterface $output
    ): void {
        $sourceTable = $table->sourceTable();

        if (!$this->gateway->hasMaskColumn($table)) {
            $output->writeln("<info>{$sourceTable} has no \"{$table->maskColumn()}\" column - already migrated, nothing to backfill.</info>");

            return;
        }

        if ($dryRun) {
            $output->writeln(sprintf(
                '<info>[dry-run] %s: %d row(s) would be inserted, %d always-available flag(s) would be updated.</info>',
                $sourceTable,
                $this->gateway->countMissingTranslations($table),
                $this->gateway->countWrongAlwaysAvailableFlags($table)
            ));

            return;
        }

        $range = $this->gateway->getIdRange($table);
        if ($range === null) {
            $output->writeln("<info>{$sourceTable} is empty, nothing to backfill.</info>");

            return;
        }

        [$minId, $maxId] = $range;
        $output->writeln("<info>Backfilling {$sourceTable} (id {$minId}..{$maxId}, batch size {$batchSize})...</info>");

        $totalInserted = 0;
        $totalFlagged = 0;
        for ($from = $minId; $from <= $maxId; $from += $batchSize) {
            $to = min($from + $batchSize - 1, $maxId);
            $inserted = $this->gateway->insertMissingTranslations($table, $from, $to);
            $flagged = $this->gateway->fixAlwaysAvailableFlags($table, $from, $to);

            $totalInserted += $inserted;
            $totalFlagged += $flagged;
            $output->writeln(
                sprintf('  %d..%d: %d row(s), %d always-available flag(s)', $from, $to, $inserted, $flagged),
                OutputInterface::VERBOSITY_VERBOSE
            );
        }

        $output->writeln(sprintf(
            '<info>%s: %d row(s) inserted, %d always-available flag(s) updated.</info>',
            $sourceTable,
            $totalInserted,
            $totalFlagged
        ));
    }
}
