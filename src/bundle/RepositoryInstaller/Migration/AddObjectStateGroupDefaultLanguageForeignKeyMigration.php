<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds a foreign key from "ibexa_object_state_group.default_language_id" to "ibexa_content_language.id".
 */
final class AddObjectStateGroupDefaultLanguageForeignKeyMigration extends AbstractAddLanguageForeignKeyMigration
{
    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-16 00:00:07');
    }

    protected function getTable(): string
    {
        return 'ibexa_object_state_group';
    }

    protected function getColumn(): string
    {
        return 'default_language_id';
    }

    protected function getForeignKey(): string
    {
        return 'ibexa_object_state_group_default_language_fk';
    }
}
