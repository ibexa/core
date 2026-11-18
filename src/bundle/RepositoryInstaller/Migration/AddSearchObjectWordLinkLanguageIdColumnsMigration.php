<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds "language_id" and "is_main_and_always_available" to "ibexa_search_object_word_link", backfilled from "language_mask".
 */
final class AddSearchObjectWordLinkLanguageIdColumnsMigration extends AbstractAddColumnsFromLanguageMaskMigration
{
    public function getDescription(): string
    {
        return 'Adds "language_id" and "is_main_and_always_available" columns to "ibexa_search_object_word_link", backfilled from the language mask';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 03:00:00');
    }

    protected function getTable(): string
    {
        return 'ibexa_search_object_word_link';
    }

    protected function getLanguageMaskColumn(): string
    {
        return 'language_mask';
    }

    protected function getAddColumnSqlFiles(): array
    {
        return [
            'language_id' => 'add-search-object-word-link-language-id-column',
            'is_main_and_always_available' => 'add-search-object-word-link-always-available-column',
        ];
    }

    protected function getBackfillSqlFile(): string
    {
        return 'backfill-search-object-word-link-language-ids.sql';
    }
}
