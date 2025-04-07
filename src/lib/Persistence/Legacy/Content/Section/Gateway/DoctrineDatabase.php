<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Section\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Core\Persistence\Legacy\Content\Section\Gateway;

/**
 * @internal Gateway implementation is considered internal. Use Persistence Section Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\Section\Handler
 */
final class DoctrineDatabase extends Gateway
{
    private Connection $connection;

    /**
     * Creates a new DoctrineDatabase Section Gateway.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertSection(string $name, string $identifier): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_SECTION_TABLE)
            ->values(
                [
                    'name' => $query->createPositionalParameter($name),
                    'identifier' => $query->createPositionalParameter($identifier),
                ]
            );

        $query->executeStatement();

        return (int)$this->connection->lastInsertId(Gateway::CONTENT_SECTION_SEQ);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateSection(int $id, string $name, string $identifier): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::CONTENT_SECTION_TABLE)
            ->set('name', $query->createPositionalParameter($name))
            ->set('identifier', $query->createPositionalParameter($identifier))
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadSectionData(int $id): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('id', 'identifier', 'name')
            ->from(self::CONTENT_SECTION_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadAllSectionData(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('id', 'identifier', 'name')
            ->from(self::CONTENT_SECTION_TABLE);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadSectionDataByIdentifier(string $identifier): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'id',
            'identifier',
            'name'
        )->from(
            self::CONTENT_SECTION_TABLE
        )->where(
            $query->expr()->eq(
                'identifier',
                $query->createPositionalParameter($identifier, ParameterType::STRING)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countContentObjectsInSection(int $id): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'COUNT(id)'
        )->from(
            'ezcontentobject'
        )->where(
            $query->expr()->eq(
                'section_id',
                $query->createPositionalParameter($id, ParameterType::INTEGER)
            )
        );

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countPoliciesUsingSection(int $id): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(l.id)')
            ->from('ezpolicy_limitation', 'l')
            ->join(
                'l',
                'ezpolicy_limitation_value',
                'lv',
                $expr->eq(
                    'l.id',
                    'lv.limitation_id'
                )
            )
            ->where(
                $expr->eq(
                    'l.identifier',
                    $query->createPositionalParameter('Section', ParameterType::STRING)
                )
            )
            ->andWhere(
                $expr->eq(
                    'lv.value',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            )
        ;

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countRoleAssignmentsUsingSection(int $id): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(ur.id)')
            ->from('ezuser_role', 'ur')
            ->where(
                $expr->eq(
                    'ur.limit_identifier',
                    $query->createPositionalParameter('Section', ParameterType::STRING)
                )
            )
            ->andWhere(
                $expr->eq(
                    'ur.limit_value',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            )
        ;

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteSection(int $id): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_SECTION_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function assignSectionToContent(int $sectionId, int $contentId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update('ezcontentobject')
            ->set(
                'section_id',
                $query->createPositionalParameter($sectionId, ParameterType::INTEGER)
            )
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }
}
