<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds an "always_available" column to "ibexa_url_alias_ml", backfilled from bit 0 of "lang_mask".
 */
final class AddUrlAliasAlwaysAvailableColumnMigration extends AbstractAddColumnsFromLanguageMaskMigration
{
    public function getDescription(): string
    {
        return 'Adds "always_available" column to "ibexa_url_alias_ml", backfilled from the language mask';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 04:00:00');
    }

    protected function getTable(): string
    {
        return 'ibexa_url_alias_ml';
    }

    protected function getLanguageMaskColumn(): string
    {
        return 'lang_mask';
    }

    protected function getAddColumnSqlFiles(): array
    {
        return [
            'always_available' => 'add-url-alias-always-available-column',
        ];
    }

    protected function getBackfillSqlFile(): string
    {
        return 'backfill-url-alias-always-available.sql';
    }
}
