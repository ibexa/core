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
    private const string CONTROLS_PARAM_NAME = ':controls';
    private const string HAS_CONTROLLER_PARAM_NAME = ':hasController';
    private const string HEIGHT_PARAM_NAME = ':height';
    private const string IS_AUTOPLAY_PARAM_NAME = ':isAutoplay';
    private const string IS_LOOP_PARAM_NAME = ':isLoop';
    private const string PLUGINS_PAGE_PAGE = ':pluginsPage';
    private const string QUALITY_PARAM_NAME = ':quality';
    private const string WIDTH_PARAM_NAME = ':width';

    /**
     * {@inheritdoc}
     */
    protected function getStorageTable(): string
    {
        return 'ezmedia';
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

        $queryBuilder->addSelect($this->connection->quoteIdentifier('has_controller'), $this->connection->quoteIdentifier('is_autoplay'), $this->connection->quoteIdentifier('is_loop'), $this->connection->quoteIdentifier('width'), $this->connection->quoteIdentifier('height'));
    }

    /**
     * {@inheritdoc}
     */
    protected function setInsertColumns(QueryBuilder $queryBuilder, VersionInfo $versionInfo, Field $field)
    {
        parent::setInsertColumns($queryBuilder, $versionInfo, $field);

        $queryBuilder
            ->setValue('controls', self::CONTROLS_PARAM_NAME)
            ->setValue('has_controller', self::HAS_CONTROLLER_PARAM_NAME)
            ->setValue('height', self::HEIGHT_PARAM_NAME)
            ->setValue('is_autoplay', self::IS_AUTOPLAY_PARAM_NAME)
            ->setValue('is_loop', self::IS_LOOP_PARAM_NAME)
            ->setValue('pluginspage', self::PLUGINS_PAGE_PAGE)
            ->setValue('quality', self::QUALITY_PARAM_NAME)
            ->setValue('width', self::WIDTH_PARAM_NAME)
            ->setParameter(self::CONTROLS_PARAM_NAME, '')
            ->setParameter(
                self::HAS_CONTROLLER_PARAM_NAME,
                $field->value->externalData['hasController'],
                PDO::PARAM_INT
            )
            ->setParameter(self::HEIGHT_PARAM_NAME, $field->value->externalData['height'], PDO::PARAM_INT)
            ->setParameter(self::IS_AUTOPLAY_PARAM_NAME, $field->value->externalData['autoplay'], PDO::PARAM_INT)
            ->setParameter(self::IS_LOOP_PARAM_NAME, $field->value->externalData['loop'], PDO::PARAM_INT)
            ->setParameter(self::PLUGINS_PAGE_PAGE, '')
            ->setParameter(self::QUALITY_PARAM_NAME, 'high')
            ->setParameter(self::WIDTH_PARAM_NAME, $field->value->externalData['width'], PDO::PARAM_INT)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUpdateColumns(QueryBuilder $queryBuilder, VersionInfo $versionInfo, Field $field)
    {
        parent::setUpdateColumns($queryBuilder, $versionInfo, $field);

        $queryBuilder
            ->set('controls', self::CONTROLS_PARAM_NAME)
            ->set('has_controller', self::HAS_CONTROLLER_PARAM_NAME)
            ->set('height', self::HEIGHT_PARAM_NAME)
            ->set('is_autoplay', self::IS_AUTOPLAY_PARAM_NAME)
            ->set('is_loop', self::IS_LOOP_PARAM_NAME)
            ->set('pluginspage', self::PLUGINS_PAGE_PAGE)
            ->set('quality', self::QUALITY_PARAM_NAME)
            ->set('width', self::WIDTH_PARAM_NAME)
            ->setParameter(self::CONTROLS_PARAM_NAME, '')
            ->setParameter(
                self::HAS_CONTROLLER_PARAM_NAME,
                $field->value->externalData['hasController'],
                ParameterType::INTEGER
            )
            ->setParameter(self::HEIGHT_PARAM_NAME, $field->value->externalData['height'], ParameterType::INTEGER)
            ->setParameter(self::IS_AUTOPLAY_PARAM_NAME, $field->value->externalData['autoplay'], ParameterType::INTEGER)
            ->setParameter(self::IS_LOOP_PARAM_NAME, $field->value->externalData['loop'], ParameterType::INTEGER)
            ->setParameter(self::PLUGINS_PAGE_PAGE, '')
            ->setParameter(self::QUALITY_PARAM_NAME, 'high')
            ->setParameter(self::WIDTH_PARAM_NAME, $field->value->externalData['width'], ParameterType::INTEGER)
        ;
    }
}
