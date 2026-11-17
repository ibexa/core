<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;

/**
 * Adds a foreign key from "ibexa_content_type.initial_language_id" to "ibexa_content_language.id".
 */
final class AddContentTypeInitialLanguageForeignKeyMigration extends AbstractAddLanguageForeignKeyMigration
{
    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-16 00:00:02');
    }

    protected function getTable(): string
    {
        return 'ibexa_content_type';
    }

    protected function getColumn(): string
    {
        return 'initial_language_id';
    }

    protected function getForeignKey(): string
    {
        return 'ibexa_content_type_initial_language_fk';
    }
}
