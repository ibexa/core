<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Replaces the ON DELETE CASCADE foreign key from "ibexa_content_type_field_definition_ml.language_id" to "ibexa_content_language.id" with an ON DELETE RESTRICT one.
 */
final class AddContentTypeFieldDefinitionLanguageForeignKeyMigration extends AbstractAddLanguageForeignKeyMigration
{
    private const string REPLACED_FOREIGN_KEY = 'ibexa_content_type_field_definition_ml_lang_fk';

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-16 00:00:11');
    }

    protected function getTable(): string
    {
        return 'ibexa_content_type_field_definition_ml';
    }

    protected function getColumn(): string
    {
        return 'language_id';
    }

    protected function getForeignKey(): string
    {
        return 'ibexa_content_type_field_definition_ml_language_fk';
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
     * @return non-empty-list<non-empty-string>
     */
    protected function getSqlFiles(
        AbstractSchemaManager $schemaManager,
        string $platform
    ): array {
        if ($platform === 'sqlite') {
            return parent::getSqlFiles($schemaManager, $platform);
        }

        $sqlFiles = $this->hasLanguageIdIndex($schemaManager) ? [] : [$this->getSqlFile($platform, '-index')];
        $sqlFiles[] = $schemaManager->introspectTableByUnquotedName($this->getTable())->hasForeignKey(self::REPLACED_FOREIGN_KEY)
            ? $this->getSqlFile($platform)
            : $this->getSqlFile($platform, '-without-replaced');

        return $sqlFiles;
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     */
    private function hasLanguageIdIndex(AbstractSchemaManager $schemaManager): bool
    {
        foreach ($schemaManager->introspectTableIndexesByUnquotedName($this->getTable()) as $index) {
            if ($index->getIndexedColumns()[0]->getColumnName()->getIdentifier()->getValue() === $this->getColumn()) {
                return true;
            }
        }

        return false;
    }
}
