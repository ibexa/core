<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Image\ImageStorage\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use DOMDocument;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\Image\ImageStorage\Gateway;
use Ibexa\Core\IO\UrlRedecoratorInterface;

/**
 * Image Field Type external storage DoctrineStorage gateway.
 */
class DoctrineStorage extends Gateway
{
    public const string IMAGE_FILE_TABLE = 'ezimagefile';
    private const string PATH_PARAM_NAME = 'path';
    private const string LIKE_PATH_PARAM_NAME = 'likePath';
    private const string FIELD_ID_PARAM_NAME = 'field_id';
    private const string VERSION_NO_PARAM_NAME = 'versionNo';
    private const string CONTENT_OBJECT_ID_PARAM_NAME = 'contentObjectId';

    protected Connection $connection;

    /**
     * Maps database field names to property names.
     *
     * @var array{id: string, version: string, language_code: string, path_identification_string: string, data_string: string}
     */
    protected array $fieldNameMap = [
        'id' => 'fieldId',
        'version' => 'versionNo',
        'language_code' => 'languageCode',
        'path_identification_string' => 'nodePathString',
        'data_string' => 'xml',
    ];

    private UrlRedecoratorInterface $redecorator;

    public function __construct(UrlRedecoratorInterface $redecorator, Connection $connection)
    {
        $this->redecorator = $redecorator;
        $this->connection = $connection;
    }

    /**
     * Return the node path string of $versionInfo.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getNodePathString(VersionInfo $versionInfo): string
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select($this->connection->quoteIdentifier('path_identification_string'))
            ->from($this->connection->quoteIdentifier('ezcontentobject_tree'))
            ->where(
                $selectQuery->expr()->and(
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_id'),
                        ':' . self::CONTENT_OBJECT_ID_PARAM_NAME
                    ),
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_version'),
                        ':' . self::VERSION_NO_PARAM_NAME
                    ),
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('node_id'),
                        $this->connection->quoteIdentifier('main_node_id')
                    )
                )
            )
            ->setParameter(self::CONTENT_OBJECT_ID_PARAM_NAME, $versionInfo->contentInfo->id, ParameterType::INTEGER)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $versionInfo->versionNo, ParameterType::INTEGER);

        return $selectQuery->executeQuery()->fetchOne();
    }

    /**
     * Store a reference to the image in $path for $fieldId.
     *
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     */
    public function storeImageReference(string $uri, int $fieldId): void
    {
        // legacy stores the path to the image without a leading /
        $path = $this->redecorator->redecorateFromSource($uri);

        $insertQuery = $this->connection->createQueryBuilder();
        $insertQuery
            ->insert($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
            ->values(
                [
                    $this->connection->quoteIdentifier('contentobject_attribute_id') => ':' . self::FIELD_ID_PARAM_NAME,
                    $this->connection->quoteIdentifier('filepath') => ':' . self::PATH_PARAM_NAME,
                ]
            )
            ->setParameter(self::FIELD_ID_PARAM_NAME, $fieldId, ParameterType::INTEGER)
            ->setParameter(self::PATH_PARAM_NAME, $path)
        ;

        $insertQuery->executeStatement();
    }

    /**
     * Return an XML content stored for the given $fieldIds.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getXmlForImages(int $versionNo, array $fieldIds): array
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(
                $this->connection->quoteIdentifier('attr.id'),
                $this->connection->quoteIdentifier('attr.data_text')
            )
            ->from($this->connection->quoteIdentifier('ezcontentobject_attribute'), 'attr')
            ->where(
                $selectQuery->expr()->and(
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('attr.version'),
                        ':' . self::VERSION_NO_PARAM_NAME
                    ),
                    $selectQuery->expr()->in(
                        $this->connection->quoteIdentifier('attr.id'),
                        ':fieldIds'
                    )
                )
            )
            ->setParameter(self::VERSION_NO_PARAM_NAME, $versionNo, ParameterType::INTEGER)
            ->setParameter('fieldIds', $fieldIds, ArrayParameterType::INTEGER)
        ;

        $statement = $selectQuery->executeQuery();

        $fieldLookup = [];
        foreach ($statement->fetchAllAssociative() as $row) {
            $fieldLookup[$row['id']] = $row['data_text'];
        }

        return $fieldLookup;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAllVersionsImageXmlForFieldId(int $fieldId): array
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(
                'field.id',
                'field.version',
                'field.data_text'
            )
            ->from($this->connection->quoteIdentifier('ezcontentobject_attribute'), 'field')
            ->where(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('id'),
                    ':' . self::FIELD_ID_PARAM_NAME
                )
            )
            ->setParameter(self::FIELD_ID_PARAM_NAME, $fieldId, ParameterType::INTEGER)
        ;

        $statement = $selectQuery->executeQuery();

        $fieldLookup = [];
        foreach ($statement->fetchAllAssociative() as $row) {
            $fieldLookup[] = [
                'version' => (int)$row['version'],
                'data_text' => $row['data_text'],
            ];
        }

        return $fieldLookup;
    }

    /**
     * Remove all references from $fieldId to a path that starts with $path.
     *
     * @param string $uri File IO uri (not legacy)
     *
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     * @throws \Doctrine\DBAL\Exception
     */
    public function removeImageReferences(string $uri, int $versionNo, int $fieldId): void
    {
        if (!$this->canRemoveImageReference($uri, $versionNo, $fieldId)) {
            return;
        }

        $path = $this->redecorator->redecorateFromSource($uri);

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
            ->where(
                $deleteQuery->expr()->and(
                    $deleteQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_attribute_id'),
                        ':' . self::FIELD_ID_PARAM_NAME
                    ),
                    $deleteQuery->expr()->like(
                        $this->connection->quoteIdentifier('filepath'),
                        ':' . self::LIKE_PATH_PARAM_NAME
                    )
                )
            )
            ->setParameter(self::FIELD_ID_PARAM_NAME, $fieldId, ParameterType::INTEGER)
            ->setParameter(self::LIKE_PATH_PARAM_NAME, $path . '%')
        ;

        $deleteQuery->executeStatement();
    }

    /**
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     * @throws \Doctrine\DBAL\Exception
     */
    public function isImageReferenced(string $uri): bool
    {
        $path = $this->redecorator->redecorateFromSource($uri);

        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('1')
            ->from($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
            ->where(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('filepath'),
                    ':' . self::LIKE_PATH_PARAM_NAME
                )
            )
            ->setParameter(self::LIKE_PATH_PARAM_NAME, $path)
        ;

        $statement = $selectQuery->executeQuery();

        return (bool)$statement->fetchOne();
    }

    /**
     * Return the number of recorded references to the given $path.
     *
     * @param string $uri File IO uri (not legacy)
     *
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     * @throws \Doctrine\DBAL\Exception
     */
    public function countImageReferences(string $uri): int
    {
        $path = $this->redecorator->redecorateFromSource($uri);

        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('COUNT(' . $this->connection->quoteIdentifier('id') . ')')
            ->from($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
            ->where(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('filepath'),
                    ':filepath'
                )
            )
            ->setParameter('filepath', $path)
        ;

        $statement = $selectQuery->executeQuery();

        return (int) $statement->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countDistinctImagesData(): int
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('COUNT(id)')
            ->from($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
        ;

        $statement = $selectQuery->executeQuery();

        return (int) $statement->fetchOne();
    }

    /**
     * Check if image $path can be removed when deleting $versionNo and $fieldId.
     *
     * @param string $path legacy image path (var/storage/images...)
     *
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     * @throws \Doctrine\DBAL\Exception
     */
    protected function canRemoveImageReference(string $path, int $versionNo, int $fieldId): bool
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $expressionBuilder = $selectQuery->expr();
        $selectQuery
            ->select('attr.data_text')
            ->from($this->connection->quoteIdentifier('ezcontentobject_attribute'), 'attr')
            ->innerJoin(
                'attr',
                $this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE),
                'img',
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('img.contentobject_attribute_id'),
                    $this->connection->quoteIdentifier('attr.id')
                )
            )
            ->where(
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('contentobject_attribute_id'),
                    ':' . self::FIELD_ID_PARAM_NAME
                )
            )
            ->andWhere(
                $expressionBuilder->neq(
                    $this->connection->quoteIdentifier('version'),
                    ':' . self::VERSION_NO_PARAM_NAME
                )
            )
            ->setParameter(self::FIELD_ID_PARAM_NAME, $fieldId, ParameterType::INTEGER)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $versionNo, ParameterType::INTEGER)
        ;

        $imageXMLs = $selectQuery->executeQuery()->fetchFirstColumn();
        foreach ($imageXMLs as $imageXML) {
            $storedFilePath = $this->extractFilesFromXml($imageXML)['original'] ?? null;
            if ($storedFilePath === $path) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     */
    public function extractFilesFromXml(?string $xml): ?array
    {
        if (empty($xml)) {
            // Empty image value
            return null;
        }

        $files = [];

        $dom = new DOMDocument();
        $dom->loadXml($xml);
        if ($dom->documentElement?->hasAttribute('dirpath')) {
            $url = $dom->documentElement->getAttribute('url');
            if (empty($url)) {
                return null;
            }

            $files['original'] = $this->redecorator->redecorateFromTarget($url);
            foreach ($dom->documentElement->childNodes as $childNode) {
                /** @var \DOMElement $childNode */
                if ($childNode->nodeName !== 'alias') {
                    continue;
                }

                $files[$childNode->getAttribute('name')] = $this->redecorator->redecorateFromTarget(
                    $childNode->getAttribute('url')
                );
            }

            return $files;
        }

        return null;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getImagesData(int $offset, int $limit): array
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(
                'img.contentobject_attribute_id',
                'img.filepath'
            )
            ->distinct()
            ->from($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE), 'img')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $selectQuery->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateImageData(int $fieldId, int $versionNo, string $xml): void
    {
        $updateQuery = $this->connection->createQueryBuilder();
        $expressionBuilder = $updateQuery->expr();
        $updateQuery
            ->update(
                $this->connection->quoteIdentifier('ezcontentobject_attribute')
            )
            ->set(
                $this->connection->quoteIdentifier('data_text'),
                ':xml'
            )
            ->where(
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('id'),
                    ':' . self::FIELD_ID_PARAM_NAME
                )
            )
            ->andWhere(
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('version'),
                    ':version_no'
                )
            )
            ->setParameter(self::FIELD_ID_PARAM_NAME, $fieldId, ParameterType::INTEGER)
            ->setParameter('version_no', $versionNo, ParameterType::INTEGER)
            ->setParameter('xml', $xml)
            ->executeStatement()
        ;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateImagePath(int $fieldId, string $oldPath, string $newPath): void
    {
        $updateQuery = $this->connection->createQueryBuilder();
        $expressionBuilder = $updateQuery->expr();
        $updateQuery
            ->update(
                $this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE)
            )
            ->set(
                $this->connection->quoteIdentifier('filepath'),
                ':new_path'
            )
            ->where(
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('contentobject_attribute_id'),
                    ':' . self::FIELD_ID_PARAM_NAME
                )
            )
            ->andWhere(
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('filepath'),
                    ':old_path'
                )
            )
            ->setParameter(self::FIELD_ID_PARAM_NAME, $fieldId, ParameterType::INTEGER)
            ->setParameter('old_path', $oldPath)
            ->setParameter('new_path', $newPath)
            ->executeStatement()
        ;
    }

    /**
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     * @throws \Doctrine\DBAL\Exception
     */
    public function hasImageReference(string $uri, int $fieldId): bool
    {
        $path = $this->redecorator->redecorateFromSource($uri);

        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('1')
            ->from($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
            ->andWhere(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('filepath'),
                    ':' . self::PATH_PARAM_NAME
                )
            )
            ->andWhere(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('contentobject_attribute_id'),
                    ':' . self::FIELD_ID_PARAM_NAME
                )
            )
            ->setParameter(self::PATH_PARAM_NAME, $path)
            ->setParameter(self::FIELD_ID_PARAM_NAME, $fieldId)
        ;

        $statement = $selectQuery->executeQuery();

        return (bool)$statement->fetchOne();
    }
}
