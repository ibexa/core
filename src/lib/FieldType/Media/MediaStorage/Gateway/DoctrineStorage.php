<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Media\MediaStorage\Gateway;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\BinaryBase\BinaryBaseStorage\Gateway\DoctrineStorage as BaseDoctrineStorage;
use PDO;

/**
 * Media Field Type external storage DoctrineStorage gateway.
 */
class DoctrineStorage extends BaseDoctrineStorage
{
    /**
     * {@inheritdoc}
     */
    protected function getStorageTable(): string
    {
        return 'ibexa_media';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPropertyMapping()
    {
        $propertyMap = parent::getPropertyMapping();
        $propertyMap['has_controller'] = [
            'name' => 'hasController',
            'cast' => static function ($val): bool {
                return (bool)$val;
            },
        ];
        $propertyMap['is_autoplay'] = [
            'name' => 'autoplay',
            'cast' => static function ($val): bool {
                return (bool)$val;
            },
        ];
        $propertyMap['is_loop'] = [
            'name' => 'loop',
            'cast' => static function ($val): bool {
                return (bool)$val;
            },
        ];
        $propertyMap['width'] = [
            'name' => 'width',
            'cast' => 'intval',
        ];
        $propertyMap['height'] = [
            'name' => 'height',
            'cast' => 'intval',
        ];

        return $propertyMap;
    }

    /**
     * {@inheritdoc}
     */
    protected function setFetchColumns(QueryBuilder $queryBuilder, $fieldId, $versionNo)
    {
        parent::setFetchColumns($queryBuilder, $fieldId, $versionNo);

        $queryBuilder->addSelect(
            $this->connection->quoteIdentifier('has_controller'),
            $this->connection->quoteIdentifier('is_autoplay'),
            $this->connection->quoteIdentifier('is_loop'),
            $this->connection->quoteIdentifier('width'),
            $this->connection->quoteIdentifier('height')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function setInsertColumns(QueryBuilder $queryBuilder, VersionInfo $versionInfo, Field $field)
    {
        parent::setInsertColumns($queryBuilder, $versionInfo, $field);

        $queryBuilder
            ->setValue('controls', ':controls')
            ->setValue('has_controller', ':hasController')
            ->setValue('height', ':height')
            ->setValue('is_autoplay', ':isAutoplay')
            ->setValue('is_loop', ':isLoop')
            ->setValue('pluginspage', ':pluginsPage')
            ->setValue('quality', ':quality')
            ->setValue('width', ':width')
            ->setParameter('controls', '')
            ->setParameter(
                'hasController',
                $field->value->externalData['hasController'],
                PDO::PARAM_INT
            )
            ->setParameter('height', $field->value->externalData['height'], PDO::PARAM_INT)
            ->setParameter('isAutoplay', $field->value->externalData['autoplay'], PDO::PARAM_INT)
            ->setParameter('isLoop', $field->value->externalData['loop'], PDO::PARAM_INT)
            ->setParameter('pluginsPage', '')
            ->setParameter('quality', 'high')
            ->setParameter('width', $field->value->externalData['width'], PDO::PARAM_INT)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUpdateColumns(QueryBuilder $queryBuilder, VersionInfo $versionInfo, Field $field)
    {
        parent::setUpdateColumns($queryBuilder, $versionInfo, $field);

        $queryBuilder
            ->set('controls', ':controls')
            ->set('has_controller', ':hasController')
            ->set('height', ':height')
            ->set('is_autoplay', ':isAutoplay')
            ->set('is_loop', ':isLoop')
            ->set('pluginspage', ':pluginsPage')
            ->set('quality', ':quality')
            ->set('width', ':width')
            ->setParameter('controls', '')
            ->setParameter(
                'hasController',
                $field->value->externalData['hasController'],
                ParameterType::INTEGER
            )
            ->setParameter('height', $field->value->externalData['height'], ParameterType::INTEGER)
            ->setParameter('isAutoplay', $field->value->externalData['autoplay'], ParameterType::INTEGER)
            ->setParameter('isLoop', $field->value->externalData['loop'], ParameterType::INTEGER)
            ->setParameter('pluginsPage', '')
            ->setParameter('quality', 'high')
            ->setParameter('width', $field->value->externalData['width'], ParameterType::INTEGER)
        ;
    }
}
