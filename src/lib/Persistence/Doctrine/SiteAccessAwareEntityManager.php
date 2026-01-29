<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Ibexa\Bundle\Core\Entity\EntityManagerFactory;

/**
 * @internal
 */
final class SiteAccessAwareEntityManager implements EntityManagerInterface
{
    private ?EntityManagerInterface $resolvedEntityManager = null;

    public function __construct(
        private readonly EntityManagerFactory $entityManagerFactory,
    ) {
    }

    private function getWrapped(): EntityManagerInterface
    {
        return $this->resolvedEntityManager ??= $this->entityManagerFactory->getEntityManager();
    }

    public function getConnection(): Connection
    {
        return $this->getWrapped()->getConnection();
    }

    public function getExpressionBuilder(): Expr
    {
        return $this->getWrapped()->getExpressionBuilder();
    }

    public function beginTransaction(): void
    {
        $this->getWrapped()->beginTransaction();
    }

    public function transactional($func): mixed
    {
        return $this->getWrapped()->transactional($func);
    }

    public function wrapInTransaction(callable $func): mixed
    {
        return $this->getWrapped()->wrapInTransaction($func);
    }

    public function commit(): void
    {
        $this->getWrapped()->commit();
    }

    public function rollback(): void
    {
        $this->getWrapped()->rollback();
    }

    public function createQuery($dql = ''): Query
    {
        return $this->getWrapped()->createQuery($dql);
    }

    public function createNamedQuery($name): Query
    {
        return $this->getWrapped()->createNamedQuery($name);
    }

    public function createNativeQuery($sql, ResultSetMapping $rsm): NativeQuery
    {
        return $this->getWrapped()->createNativeQuery($sql, $rsm);
    }

    public function createNamedNativeQuery($name): NativeQuery
    {
        return $this->getWrapped()->createNamedNativeQuery($name);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->getWrapped()->createQueryBuilder();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entityName
     *
     * @return T|null
     */
    public function getReference($entityName, $id): ?object
    {
        return $this->getWrapped()->getReference($entityName, $id);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entityName
     *
     * @return T|null
     */
    public function getPartialReference($entityName, $identifier): ?object
    {
        return $this->getWrapped()->getPartialReference($entityName, $identifier);
    }

    public function close(): void
    {
        $this->getWrapped()->close();
    }

    /**
     * @template T of object
     *
     * @param T $entity
     * @param bool $deep
     *
     * @return T
     */
    public function copy($entity, $deep = false): object
    {
        /** @var T */
        return $this->getWrapped()->copy($entity, $deep);
    }

    public function lock($entity, $lockMode, $lockVersion = null): void
    {
        $this->getWrapped()->lock($entity, $lockMode, $lockVersion);
    }

    public function getEventManager(): \Doctrine\Common\EventManager
    {
        return $this->getWrapped()->getEventManager();
    }

    public function getConfiguration(): Configuration
    {
        return $this->getWrapped()->getConfiguration();
    }

    public function isOpen(): bool
    {
        return $this->getWrapped()->isOpen();
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->getWrapped()->getUnitOfWork();
    }

    public function getHydrator($hydrationMode): AbstractHydrator
    {
        return $this->getWrapped()->getHydrator($hydrationMode);
    }

    public function newHydrator($hydrationMode): AbstractHydrator
    {
        return $this->getWrapped()->newHydrator($hydrationMode);
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->getWrapped()->getProxyFactory();
    }

    public function getFilters(): FilterCollection
    {
        return $this->getWrapped()->getFilters();
    }

    public function isFiltersStateClean(): bool
    {
        return $this->getWrapped()->isFiltersStateClean();
    }

    public function hasFilters(): bool
    {
        return $this->getWrapped()->hasFilters();
    }

    public function getCache(): ?Cache
    {
        return $this->getWrapped()->getCache();
    }

    public function find($className, $id): ?object
    {
        return $this->getWrapped()->find($className, $id);
    }

    public function persist(object $object): void
    {
        $this->getWrapped()->persist($object);
    }

    public function remove(object $object): void
    {
        $this->getWrapped()->remove($object);
    }

    public function clear(): void
    {
        $this->getWrapped()->clear();
    }

    public function detach(object $object): void
    {
        $this->getWrapped()->detach($object);
    }

    public function refresh(object $object, ?int $lockMode = null): void
    {
        $this->getWrapped()->refresh($object, $lockMode);
    }

    public function flush(): void
    {
        $this->getWrapped()->flush();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return EntityRepository<T>
     */
    public function getRepository($className): EntityRepository
    {
        return $this->getWrapped()->getRepository($className);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return ClassMetadata<T>
     */
    public function getClassMetadata($className): ClassMetadata
    {
        return $this->getWrapped()->getClassMetadata($className);
    }

    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->getWrapped()->getMetadataFactory();
    }

    public function initializeObject(object $obj): void
    {
        $this->getWrapped()->initializeObject($obj);
    }

    public function isUninitializedObject(mixed $value): bool
    {
        return $this->getWrapped()->isUninitializedObject($value);
    }

    public function contains(object $object): bool
    {
        return $this->getWrapped()->contains($object);
    }
}
