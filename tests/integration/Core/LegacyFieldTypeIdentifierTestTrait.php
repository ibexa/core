<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;

trait LegacyFieldTypeIdentifierTestTrait
{
    abstract protected function getRawDatabaseConnection(): Connection;

    /**
     * Sets `ibexa_content_field.data_type_string` to a legacy alias, leaving `ibexa_class_attribute` untouched.
     *
     * @throws \ErrorException
     * @throws \Doctrine\DBAL\Exception
     */
    protected function downgradeFieldTypeIdentifierToLegacyAlias(
        string $legacyAlias,
        int $contentId,
        int $versionNo,
        int $fieldDefinitionId
    ): void {
        $connection = $this->getRawDatabaseConnection();

        $query = $connection->createQueryBuilder();
        $query
            ->update(Gateway::CONTENT_FIELD_TABLE)
            ->set('data_type_string', ':data_type_string')
            ->setParameter('data_type_string', $legacyAlias, ParameterType::STRING)
            ->andWhere('content_type_field_definition_id = :content_type_field_definition_id')
            ->andWhere('version = :version')
            ->andWhere('contentobject_id = :contentobject_id')
            ->setParameter('content_type_field_definition_id', $fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('version', $versionNo, ParameterType::INTEGER)
            ->setParameter('contentobject_id', $contentId, ParameterType::INTEGER);

        $query->executeStatement();
    }
}
