<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\MapLocation\MapLocationStorage\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\MapLocation\MapLocationStorage\Gateway;
use PDO;

class DoctrineStorage extends Gateway
{
    public const string MAP_LOCATION_TABLE = 'ezgmaplocation';
    private const string LATITUDE_PARAM_NAME = ':latitude';
    private const string LONGITUDE_PARAM_NAME = ':longitude';
    private const string ADDRESS_PARAM_NAME = ':address';
    private const string FIELD_ID_PARAM_NAME = ':fieldId';
    private const string VERSION_NO_PARAM_NAME = ':versionNo';

    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field): bool
    {
        if ($field->value->externalData === null) {
            // Store empty value and return
            $this->deleteFieldData($versionInfo, [$field->id]);
            $field->value->data = [
                'sortKey' => null,
                'hasData' => false,
            ];

            return false;
        }

        if ($this->hasFieldData($field->id, $versionInfo->versionNo)) {
            $this->updateFieldData($versionInfo, $field);
        } else {
            $this->storeNewFieldData($versionInfo, $field);
        }

        $field->value->data = [
            'sortKey' => $field->value->externalData['address'],
            'hasData' => true,
        ];

        return true;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function updateFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $updateQuery = $this->connection->createQueryBuilder();
        $updateQuery->update($this->connection->quoteIdentifier(self::MAP_LOCATION_TABLE))
            ->set($this->connection->quoteIdentifier('latitude'), self::LATITUDE_PARAM_NAME)
            ->set($this->connection->quoteIdentifier('longitude'), self::LONGITUDE_PARAM_NAME)
            ->set($this->connection->quoteIdentifier('address'), self::ADDRESS_PARAM_NAME)
            ->where(
                $updateQuery->expr()->and(
                    $updateQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_attribute_id'),
                        self::FIELD_ID_PARAM_NAME
                    ),
                    $updateQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_version'),
                        self::VERSION_NO_PARAM_NAME
                    )
                )
            )
            ->setParameter(self::LATITUDE_PARAM_NAME, $field->value->externalData['latitude'])
            ->setParameter(self::LONGITUDE_PARAM_NAME, $field->value->externalData['longitude'])
            ->setParameter(self::ADDRESS_PARAM_NAME, $field->value->externalData['address'])
            ->setParameter(self::FIELD_ID_PARAM_NAME, $field->id, ParameterType::INTEGER)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $versionInfo->versionNo, ParameterType::INTEGER)
        ;

        $updateQuery->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function storeNewFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $insertQuery = $this->connection->createQueryBuilder();
        $insertQuery
            ->insert($this->connection->quoteIdentifier(self::MAP_LOCATION_TABLE))
            ->values([
                'latitude' => self::LATITUDE_PARAM_NAME,
                'longitude' => self::LONGITUDE_PARAM_NAME,
                'address' => self::ADDRESS_PARAM_NAME,
                'contentobject_attribute_id' => self::FIELD_ID_PARAM_NAME,
                'contentobject_version' => self::VERSION_NO_PARAM_NAME,
            ])
            ->setParameter(self::LATITUDE_PARAM_NAME, $field->value->externalData['latitude'])
            ->setParameter(self::LONGITUDE_PARAM_NAME, $field->value->externalData['longitude'])
            ->setParameter(self::ADDRESS_PARAM_NAME, $field->value->externalData['address'])
            ->setParameter(self::FIELD_ID_PARAM_NAME, $field->id)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $versionInfo->versionNo)
        ;

        $insertQuery->executeStatement();
    }

    public function getFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $field->value->externalData = $this->loadFieldData($field->id, $versionInfo->versionNo);
    }

    /**
     * Return the data for the given $fieldId.
     *
     * If no data is found, null is returned.
     *
     * @return array{latitude: float, longitude: float}|null
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function loadFieldData(int $fieldId, int $versionNo): ?array
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(
                $this->connection->quoteIdentifier('latitude'),
                $this->connection->quoteIdentifier('longitude'),
                $this->connection->quoteIdentifier('address')
            )
            ->from($this->connection->quoteIdentifier('ezgmaplocation'))
            ->where(
                $selectQuery->expr()->and(
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_attribute_id'),
                        self::FIELD_ID_PARAM_NAME
                    ),
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_version'),
                        self::VERSION_NO_PARAM_NAME
                    )
                )
            )
            ->setParameter(self::FIELD_ID_PARAM_NAME, $fieldId, PDO::PARAM_INT)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $versionNo, PDO::PARAM_INT)
        ;

        $statement = $selectQuery->executeQuery();

        $rows = $statement->fetchAllAssociative();
        if (!isset($rows[0])) {
            return null;
        }

        // Cast coordinates as the DB can return them as strings
        $rows[0]['latitude'] = (float)$rows[0]['latitude'];
        $rows[0]['longitude'] = (float)$rows[0]['longitude'];

        return $rows[0];
    }

    /**
     * Return if field data exists for $fieldId.
     *
     * @param int $fieldId
     * @param int $versionNo
     *
     * @return bool
     */
    protected function hasFieldData($fieldId, $versionNo): bool
    {
        return $this->loadFieldData($fieldId, $versionNo) !== null;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds): void
    {
        if (empty($fieldIds)) {
            // Nothing to do
            return;
        }

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete($this->connection->quoteIdentifier(self::MAP_LOCATION_TABLE))
            ->where(
                $deleteQuery->expr()->and(
                    $deleteQuery->expr()->in(
                        $this->connection->quoteIdentifier('contentobject_attribute_id'),
                        ':fieldIds'
                    ),
                    $deleteQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_version'),
                        self::VERSION_NO_PARAM_NAME
                    )
                )
            )
            ->setParameter(':fieldIds', $fieldIds, ArrayParameterType::INTEGER)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $versionInfo->versionNo, ParameterType::INTEGER)
        ;

        $deleteQuery->executeStatement();
    }
}
