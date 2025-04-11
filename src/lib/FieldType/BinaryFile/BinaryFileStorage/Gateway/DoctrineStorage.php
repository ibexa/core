<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\BinaryFile\BinaryFileStorage\Gateway;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\BinaryBase\BinaryBaseStorage\Gateway\DoctrineStorage as BaseDoctrineStorage;

/**
 * Binary File Field Type external storage DoctrineStorage gateway.
 */
class DoctrineStorage extends BaseDoctrineStorage
{
    protected function getStorageTable(): string
    {
        return 'ezbinaryfile';
    }

    protected function getPropertyMapping(): array
    {
        $propertyMap = parent::getPropertyMapping();
        $propertyMap['download_count'] = [
            'name' => 'downloadCount',
            'cast' => 'intval',
        ];

        return $propertyMap;
    }

    protected function setFetchColumns(QueryBuilder $queryBuilder, int $fieldId, int $versionNo): void
    {
        parent::setFetchColumns($queryBuilder, $fieldId, $versionNo);

        $queryBuilder->addSelect(
            $this->connection->quoteIdentifier('download_count')
        );
    }

    protected function setInsertColumns(QueryBuilder $queryBuilder, VersionInfo $versionInfo, Field $field): void
    {
        parent::setInsertColumns($queryBuilder, $versionInfo, $field);

        $queryBuilder
            ->setValue('download_count', ':downloadCount')
            ->setParameter(
                ':downloadCount',
                $field->value->externalData['downloadCount'],
                ParameterType::INTEGER
            )
        ;
    }
}
