<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds a foreign key from "ibexa_object_state_link.contentobject_state_id" to "ibexa_object_state.id".
 */
final class AddObjectStateLinkStateForeignKeyMigration extends AbstractAddObjectStateForeignKeyMigration
{
    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-18 00:00:03');
    }

    protected function getTable(): string
    {
        return 'ibexa_object_state_link';
    }

    protected function getColumn(): string
    {
        return 'contentobject_state_id';
    }

    protected function getReferencedTable(): string
    {
        return 'ibexa_object_state';
    }

    protected function getForeignKey(): string
    {
        return 'ibexa_object_state_link_state_fk';
    }
}
