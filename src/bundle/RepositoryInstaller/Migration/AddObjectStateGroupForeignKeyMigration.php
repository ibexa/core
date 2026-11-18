<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds a foreign key from "ibexa_object_state.group_id" to "ibexa_object_state_group.id".
 */
final class AddObjectStateGroupForeignKeyMigration extends AbstractAddObjectStateForeignKeyMigration
{
    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-18 00:00:00');
    }

    protected function getTable(): string
    {
        return 'ibexa_object_state';
    }

    protected function getColumn(): string
    {
        return 'group_id';
    }

    protected function getReferencedTable(): string
    {
        return 'ibexa_object_state_group';
    }

    protected function getForeignKey(): string
    {
        return 'ibexa_object_state_group_fk';
    }
}
