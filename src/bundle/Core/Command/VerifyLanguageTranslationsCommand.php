<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Closure;
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
 * Verifies "always_available" and the language translation tables against the legacy language bitmask columns.
 */
#[AsCommand(
    name: 'ibexa:languages:verify-translations',
    description: 'Verifies "always_available" and the language translation tables against the legacy language bitmask columns',
    help: <<<'EOT'
        The command <info>%command.name%</info> compares "ibexa_content_translation",
        "ibexa_content_version_translation" and "ibexa_url_alias_ml_translation" against the
        "language_mask"/"lang_mask" columns they were backfilled from, reporting:
          - <comment>missing</comment>: a language bit set in the mask with no corresponding translation row
          - <comment>orphaned</comment>: a translation row whose bit is no longer set in the mask
          - <comment>wrong always-available flag</comment>: an "always_available" value that doesn't match bit 0 of the mask

        Exits non-zero if any drift is found and <info>--fix</info> was not passed. Run this after
        <info>ibexa:languages:backfill-translations</info>, and again before relying on the translation
        tables for anything - a clean report here is the precondition for every later migration step.
        EOT,
)]
final class VerifyLanguageTranslationsCommand
{
    private const int BATCH_SIZE = 5000;

    private const string TABLE_ALL = 'all';

    public function __construct(private readonly MaskTranslationGateway $gateway) {}

    /**
     * @throws APIInvalidArgumentException
     * @throws DBALException
     */
    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Which table to verify: one of "content", "content_version", "url_alias", or "all" for all of them.', shortcut: 't')]
        string $table = self::TABLE_ALL,
        #[Option(description: 'Insert missing rows and delete orphaned rows to bring the translation table back in sync.')]
        bool $fix = false,
    ): int {
        $clean = true;
        foreach ($this->resolveTables($table) as $maskedTable) {
            $clean = $this->verifyTable($maskedTable, $fix, $output) && $clean;
        }

        if ($clean) {
            $output->writeln('<info>All checked translation tables are in sync with their language masks.</info>');

            return Command::SUCCESS;
        }

        if ($fix) {
            $output->writeln('<info>Drift found and fixed.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<error>Drift found. Re-run with --fix to correct it.</error>');

        return Command::FAILURE;
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
    private function verifyTable(
        MaskedTable $table,
        bool $fix,
        OutputInterface $output
    ): bool {
        if (!$this->gateway->hasMaskColumn($table)) {
            $output->writeln("<info>{$table->value}: {$table->sourceTable()} has no \"{$table->maskColumn()}\" column - already migrated, nothing to verify.</info>");

            return true;
        }

        $missing = $this->gateway->countMissingTranslations($table);
        $orphaned = $this->gateway->countOrphanedTranslations($table);
        $mismatched = $this->gateway->countWrongAlwaysAvailableFlags($table);

        if ($missing === 0 && $orphaned === 0 && $mismatched === 0) {
            $output->writeln("<info>{$table->value}: in sync.</info>");

            return true;
        }

        $output->writeln(sprintf(
            '<comment>%s: %d missing, %d orphaned row(s), %d wrong always-available flag(s).</comment>',
            $table->value,
            $missing,
            $orphaned,
            $mismatched
        ));

        if (!$fix) {
            return false;
        }

        if ($missing > 0) {
            $inserted = $this->runInBatches(
                $this->gateway->getIdRange($table),
                fn (int $from, int $to): int => $this->gateway->insertMissingTranslations($table, $from, $to)
            );
            $output->writeln("  inserted {$inserted} missing row(s).");
        }

        if ($orphaned > 0) {
            $deleted = $this->runInBatches(
                $this->gateway->getTranslationIdRange($table),
                fn (int $from, int $to): int => $this->gateway->deleteOrphanedTranslations($table, $from, $to)
            );
            $output->writeln("  deleted {$deleted} orphaned row(s).");
        }

        if ($mismatched > 0) {
            $updated = $this->runInBatches(
                $this->gateway->getIdRange($table),
                fn (int $from, int $to): int => $this->gateway->fixAlwaysAvailableFlags($table, $from, $to)
            );
            $output->writeln("  fixed {$updated} always-available flag(s).");
        }

        return true;
    }

    /**
     * @param array{int, int}|null $range
     * @param Closure(int, int): int $statement
     */
    private function runInBatches(
        ?array $range,
        Closure $statement
    ): int {
        if ($range === null) {
            return 0;
        }

        [$minId, $maxId] = $range;
        $affected = 0;
        for ($from = $minId; $from <= $maxId; $from += self::BATCH_SIZE) {
            $affected += $statement($from, min($from + self::BATCH_SIZE - 1, $maxId));
        }

        return $affected;
    }
}
