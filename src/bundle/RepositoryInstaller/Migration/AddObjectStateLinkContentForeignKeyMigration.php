<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds a foreign key from "ibexa_object_state_link.contentobject_id" to "ibexa_content.id".
 */
final class AddObjectStateLinkContentForeignKeyMigration extends AbstractAddObjectStateForeignKeyMigration
{
    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-18 00:00:04');
    }

    protected function getTable(): string
    {
        return 'ibexa_object_state_link';
    }

    protected function getColumn(): string
    {
        return 'contentobject_id';
    }

    protected function getReferencedTable(): string
    {
        return 'ibexa_content';
    }

    protected function getForeignKey(): string
    {
        return 'ibexa_object_state_link_content_fk';
    }

    protected function handleOrphanedRows(int $count): void
    {
        if ($count === 0) {
            return;
        }

        $this->write(sprintf('Deleting %d row(s) of "ibexa_object_state_link" whose content no longer exists', $count));
        $this->addSqlFile(self::SQL_DIRECTORY . '/delete-orphaned-object-state-links.sql');
    }
}
