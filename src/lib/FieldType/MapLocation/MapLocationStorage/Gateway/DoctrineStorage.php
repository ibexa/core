<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\MapLocation\MapLocationStorage\Gateway;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\MapLocation\MapLocationStorage\Gateway;
use PDO;

class DoctrineStorage extends Gateway
{
    public const MAP_LOCATION_TABLE = 'ibexa_map_location';

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Store the data stored in the given $field.
     *
     * Potentially rewrites data in $field and returns true, if the $field
     * needs to be updated in the database.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     *
     * @return bool If restoring of the internal field data is required
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
     * Perform an update on the field data.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     */
    protected function updateFieldData(VersionInfo $versionInfo, Field $field)
    {
        $updateQuery = $this->connection->createQueryBuilder();
        $updateQuery->update($this->connection->quoteIdentifier(self::MAP_LOCATION_TABLE))
            ->set($this->connection->quoteIdentifier('latitude'), ':latitude')
            ->set($this->connection->quoteIdentifier('longitude'), ':longitude')
            ->set($this->connection->quoteIdentifier('address'), ':address')
            ->where(
                $updateQuery->expr()->and(
                    $updateQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_attribute_id'),
                        ':fieldId'
                    ),
                    $updateQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_version'),
                        ':versionNo'
                    )
                )
            )
            ->setParameter('latitude', $field->value->externalData['latitude'])
            ->setParameter('longitude', $field->value->externalData['longitude'])
            ->setParameter('address', $field->value->externalData['address'])
            ->setParameter('fieldId', $field->id, PDO::PARAM_INT)
            ->setParameter('versionNo', $versionInfo->versionNo, PDO::PARAM_INT)
        ;

        $updateQuery->executeStatement();
    }

    /**
     * Store new field data.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     */
    protected function storeNewFieldData(VersionInfo $versionInfo, Field $field)
    {
        $insertQuery = $this->connection->createQueryBuilder();
        $insertQuery
            ->insert($this->connection->quoteIdentifier(self::MAP_LOCATION_TABLE))
            ->values([
                'latitude' => ':latitude',
                'longitude' => ':longitude',
                'address' => ':address',
                'contentobject_attribute_id' => ':fieldId',
                'contentobject_version' => ':versionNo',
            ])
            ->setParameter('latitude', $field->value->externalData['latitude'])
            ->setParameter('longitude', $field->value->externalData['longitude'])
            ->setParameter('address', $field->value->externalData['address'])
            ->setParameter('fieldId', $field->id)
            ->setParameter('versionNo', $versionInfo->versionNo)
        ;

        $insertQuery->executeStatement();
    }

    /**
     * Set the loaded field data into $field->externalData.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     *
     * @return array
     */
    public function getFieldData(VersionInfo $versionInfo, Field $field)
    {
        $field->value->externalData = $this->loadFieldData($field->id, $versionInfo->versionNo);
    }

    /**
     * Return the data for the given $fieldId.
     *
     * If no data is found, null is returned.
     *
     * @param int $fieldId
     * @param int $versionNo
     *
     * @return array|null
     */
    protected function loadFieldData($fieldId, $versionNo)
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(
                $this->connection->quoteIdentifier('latitude'),
                $this->connection->quoteIdentifier('longitude'),
                $this->connection->quoteIdentifier('address')
            )
            ->from($this->connection->quoteIdentifier(DoctrineStorage::MAP_LOCATION_TABLE))
            ->where(
                $selectQuery->expr()->and(
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_attribute_id'),
                        ':fieldId'
                    ),
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_version'),
                        ':versionNo'
                    )
                )
            )
            ->setParameter('fieldId', $fieldId, PDO::PARAM_INT)
            ->setParameter('versionNo', $versionNo, PDO::PARAM_INT)
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
     * Delete the data for all given $fieldIds.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param int[] $fieldIds
     */
    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds)
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
                        ':versionNo'
                    )
                )
            )
            ->setParameter('fieldIds', $fieldIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('versionNo', $versionInfo->versionNo, PDO::PARAM_INT)
        ;

        $deleteQuery->executeStatement();
    }
}
