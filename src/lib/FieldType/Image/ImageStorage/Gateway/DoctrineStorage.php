<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Image\ImageStorage\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use DOMDocument;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\Image\ImageStorage\Gateway;
use Ibexa\Core\IO\UrlRedecoratorInterface;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use PDO;

/**
 * Image Field Type external storage DoctrineStorage gateway.
 */
class DoctrineStorage extends Gateway
{
    public const string IMAGE_FILE_TABLE = 'ibexa_image_file';

    /**
     * @var array<string, string>
     */
    protected array $fieldNameMap = [
        'id' => 'fieldId',
        'version' => 'versionNo',
        'language_code' => 'languageCode',
        'path_identification_string' => 'nodePathString',
        'data_string' => 'xml',
    ];

    public function __construct(
        private readonly UrlRedecoratorInterface $redecorator,
        protected Connection $connection
    ) {
    }

    public function getNodePathString(VersionInfo $versionInfo): string
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select($this->connection->quoteIdentifier('path_identification_string'))
            ->from($this->connection->quoteIdentifier(LocationGateway::CONTENT_TREE_TABLE))
            ->where(
                $selectQuery->expr()->and(
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_id'),
                        ':contentObjectId'
                    ),
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('contentobject_version'),
                        ':versionNo'
                    ),
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('node_id'),
                        $this->connection->quoteIdentifier('main_node_id')
                    )
                )
            )
            ->setParameter('contentObjectId', $versionInfo->contentInfo->id, PDO::PARAM_INT)
            ->setParameter('versionNo', $versionInfo->versionNo, PDO::PARAM_INT)
        ;

        return $selectQuery->executeQuery()->fetchOne();
    }

    /**
     * Store a reference to the image in $path for $fieldId.
     */
    public function storeImageReference(string $uri, mixed $fieldId): void
    {
        // legacy stores the path to the image without a leading /
        $path = $this->redecorator->redecorateFromSource($uri);

        $insertQuery = $this->connection->createQueryBuilder();
        $insertQuery
            ->insert($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
            ->values(
                [
                    $this->connection->quoteIdentifier('contentobject_attribute_id') => ':fieldId',
                    $this->connection->quoteIdentifier('filepath') => ':path',
                ]
            )
            ->setParameter('fieldId', $fieldId, PDO::PARAM_INT)
            ->setParameter('path', $path)
        ;

        $insertQuery->executeStatement();
    }

    /**
     * Return an XML content stored for the given $fieldIds.
     */
    public function getXmlForImages(int $versionNo, array $fieldIds): array
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(
                $this->connection->quoteIdentifier('attr.id'),
                $this->connection->quoteIdentifier('attr.data_text')
            )
            ->from($this->connection->quoteIdentifier(ContentGateway::CONTENT_FIELD_TABLE), 'attr')
            ->where(
                $selectQuery->expr()->and(
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('attr.version'),
                        ':versionNo'
                    ),
                    $selectQuery->expr()->in(
                        $this->connection->quoteIdentifier('attr.id'),
                        ':fieldIds'
                    )
                )
            )
            ->setParameter('versionNo', $versionNo, PDO::PARAM_INT)
            ->setParameter('fieldIds', $fieldIds, Connection::PARAM_INT_ARRAY)
        ;

        $statement = $selectQuery->executeQuery();

        $fieldLookup = [];
        foreach ($statement->fetchAllAssociative() as $row) {
            $fieldLookup[$row['id']] = $row['data_text'];
        }

        return $fieldLookup;
    }

    public function getAllVersionsImageXmlForFieldId(int $fieldId): array
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(
                'field.id',
                'field.version',
                'field.data_text'
            )
            ->from($this->connection->quoteIdentifier(ContentGateway::CONTENT_FIELD_TABLE), 'field')
            ->where(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('id'),
                    ':field_id'
                )
            )
            ->setParameter('field_id', $fieldId, PDO::PARAM_INT)
        ;

        $statement = $selectQuery->executeQuery();

        $fieldLookup = [];
        foreach ($statement->fetchAllAssociative() as $row) {
            $fieldLookup[] = [
                'version' => $row['version'],
                'data_text' => $row['data_text'],
            ];
        }

        return $fieldLookup;
    }

    /**
     * Remove all references from $fieldId to a path that starts with $path.
     *
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     */
    public function removeImageReferences(string $uri, int $versionNo, mixed $fieldId): void
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
                        ':fieldId'
                    ),
                    $deleteQuery->expr()->like(
                        $this->connection->quoteIdentifier('filepath'),
                        ':likePath'
                    )
                )
            )
            ->setParameter('fieldId', $fieldId, PDO::PARAM_INT)
            ->setParameter('likePath', $path . '%')
        ;

        $deleteQuery->executeStatement();
    }

    /**
     * Return the number of recorded references to the given $path.
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

    public function isImageReferenced(string $uri): bool
    {
        $path = $this->redecorator->redecorateFromSource($uri);

        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(1)
            ->from($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
            ->where(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('filepath'),
                    ':likePath'
                )
            )
            ->setParameter('likePath', $path)
        ;

        $statement = $selectQuery->executeQuery();

        return (bool)$statement->fetchOne();
    }

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

    protected function canRemoveImageReference(string $path, int $versionNo, int $fieldId): bool
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $expressionBuilder = $selectQuery->expr();
        $selectQuery
            ->select('attr.data_text')
            ->from($this->connection->quoteIdentifier(ContentGateway::CONTENT_FIELD_TABLE), 'attr')
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
                    ':fieldId'
                )
            )
            ->andWhere(
                $expressionBuilder->neq(
                    $this->connection->quoteIdentifier('version'),
                    ':versionNo'
                )
            )
            ->setParameter('fieldId', $fieldId, PDO::PARAM_INT)
            ->setParameter('versionNo', $versionNo, PDO::PARAM_INT)
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
     * Extract, stored in DocBook XML, file paths.
     *
     * @return array<string, string>|null
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
        if ($dom->documentElement->hasAttribute('dirpath')) {
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

    public function updateImageData(int $fieldId, int $versionNo, string $xml): void
    {
        $updateQuery = $this->connection->createQueryBuilder();
        $expressionBuilder = $updateQuery->expr();
        $updateQuery
            ->update(
                $this->connection->quoteIdentifier(ContentGateway::CONTENT_FIELD_TABLE)
            )
            ->set(
                $this->connection->quoteIdentifier('data_text'),
                ':xml'
            )
            ->where(
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('id'),
                    ':field_id'
                )
            )
            ->andWhere(
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('version'),
                    ':version_no'
                )
            )
            ->setParameter('field_id', $fieldId, ParameterType::INTEGER)
            ->setParameter('version_no', $versionNo, ParameterType::INTEGER)
            ->setParameter('xml', $xml, ParameterType::STRING)
            ->executeStatement()
        ;
    }

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
                    ':field_id'
                )
            )
            ->andWhere(
                $expressionBuilder->eq(
                    $this->connection->quoteIdentifier('filepath'),
                    ':old_path'
                )
            )
            ->setParameter('field_id', $fieldId, ParameterType::INTEGER)
            ->setParameter('old_path', $oldPath, ParameterType::STRING)
            ->setParameter('new_path', $newPath, ParameterType::STRING)
            ->executeStatement()
        ;
    }

    /**
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function hasImageReference(string $uri, int $fieldId): bool
    {
        $path = $this->redecorator->redecorateFromSource($uri);

        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(1)
            ->from($this->connection->quoteIdentifier(self::IMAGE_FILE_TABLE))
            ->andWhere(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('filepath'),
                    ':path'
                )
            )
            ->andWhere(
                $selectQuery->expr()->eq(
                    $this->connection->quoteIdentifier('contentobject_attribute_id'),
                    ':field_id'
                )
            )
            ->setParameter('path', $path)
            ->setParameter('field_id', $fieldId)
        ;

        $statement = $selectQuery->executeQuery();

        return (bool)$statement->fetchOne();
    }
}
