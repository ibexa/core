<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds a foreign key from "ibexa_object_state_group_language.contentobject_state_group_id" to "ibexa_object_state_group.id".
 */
final class AddObjectStateGroupLanguageGroupForeignKeyMigration extends AbstractAddObjectStateForeignKeyMigration
{
    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-18 00:00:01');
    }

    protected function getTable(): string
    {
        return 'ibexa_object_state_group_language';
    }

    protected function getColumn(): string
    {
        return 'contentobject_state_group_id';
    }

    protected function getReferencedTable(): string
    {
        return 'ibexa_object_state_group';
    }

    protected function getForeignKey(): string
    {
        return 'ibexa_object_state_group_language_group_fk';
    }
}
