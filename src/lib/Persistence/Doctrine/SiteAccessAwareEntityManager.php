<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Doctrine;

use DateTimeInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
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
use Ibexa\Contracts\Core\MVC\EventSubscriber\ConfigScopeChangeSubscriber;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 *
 * Hand-delegates rather than extending Doctrine\ORM\Decorator\EntityManagerDecorator: the decorator
 * binds $wrapped once in its constructor, but this class must re-resolve the wrapped EntityManager
 * lazily after a site-access scope change.
 */
final class SiteAccessAwareEntityManager implements EntityManagerInterface, ConfigScopeChangeSubscriber, ResetInterface
{
    private EntityManagerFactory $entityManagerFactory;

    private ?EntityManagerInterface $resolvedEntityManager = null;

    public function __construct(EntityManagerFactory $entityManagerFactory)
    {
        $this->entityManagerFactory = $entityManagerFactory;
    }

    public function onConfigScopeChange(ScopeChangeEvent $event): void
    {
        $this->resolvedEntityManager = null;
    }

    public function reset(): void
    {
        $this->resolvedEntityManager = null;
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

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        return $this->getWrapped()->createNativeQuery($sql, $rsm);
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
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function getReference($entityName, $id): ?object
    {
        return $this->getWrapped()->getReference($entityName, $id);
    }

    public function close(): void
    {
        $this->getWrapped()->close();
    }

    public function lock(object $entity, LockMode|int $lockMode, DateTimeInterface|int|null $lockVersion = null): void
    {
        $this->getWrapped()->lock($entity, $lockMode, $lockVersion);
    }

    public function getEventManager(): EventManager
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

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T|null
     */
    public function find($className, $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return $this->getWrapped()->find($className, $id, $lockMode, $lockVersion);
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

    public function refresh(object $object, LockMode|int|null $lockMode = null): void
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
    public function getRepository(string $className): EntityRepository
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
    public function getClassMetadata(string $className): ClassMetadata
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
