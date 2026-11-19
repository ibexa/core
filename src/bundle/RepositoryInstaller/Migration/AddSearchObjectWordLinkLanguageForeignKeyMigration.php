<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds a foreign key from "ibexa_search_object_word_link.language_id" to "ibexa_content_language.id", deleting the rows of missing languages first.
 */
final class AddSearchObjectWordLinkLanguageForeignKeyMigration extends AbstractAddLanguageForeignKeyMigration
{
    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-16 00:00:12');
    }

    protected function getTable(): string
    {
        return 'ibexa_search_object_word_link';
    }

    protected function getColumn(): string
    {
        return 'language_id';
    }

    protected function getForeignKey(): string
    {
        return 'ibexa_search_object_word_link_language_fk';
    }

    protected function handleOrphanedLanguageIds(string $platform): void
    {
        $this->addSqlFile($this->getSqlFile($platform, '-delete-orphans'));
    }
}
