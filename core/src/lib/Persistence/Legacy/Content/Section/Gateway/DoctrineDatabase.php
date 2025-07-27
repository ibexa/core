<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Section\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Section\Gateway;
use Ibexa\Core\Persistence\Legacy\User\Role\Gateway as RoleGateway;

/**
 * @internal Gateway implementation is considered internal. Use Persistence Section Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\Section\Handler
 */
final class DoctrineDatabase extends Gateway
{
    public function __construct(private readonly Connection $connection)
    {
    }

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

    public function loadAllSectionData(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('id', 'identifier', 'name')
            ->from(self::CONTENT_SECTION_TABLE);

        return $query->executeQuery()->fetchAllAssociative();
    }

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

    public function countContentObjectsInSection(int $id): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'COUNT(id)'
        )->from(
            ContentGateway::CONTENT_ITEM_TABLE
        )->where(
            $query->expr()->eq(
                'section_id',
                $query->createPositionalParameter($id, ParameterType::INTEGER)
            )
        );

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    public function countPoliciesUsingSection(int $id): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(l.id)')
            ->from(RoleGateway::POLICY_LIMITATION_TABLE, 'l')
            ->join(
                'l',
                RoleGateway::POLICY_LIMITATION_VALUE_TABLE,
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

    public function countRoleAssignmentsUsingSection(int $id): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(ur.id)')
            ->from(RoleGateway::USER_ROLE_TABLE, 'ur')
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

    public function assignSectionToContent(int $sectionId, int $contentId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(ContentGateway::CONTENT_ITEM_TABLE)
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
