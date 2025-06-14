<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Permission;

use Exception;
use Ibexa\Contracts\Core\Repository\PermissionCriterionResolver as APIPermissionCriterionResolver;
use Ibexa\Contracts\Core\Repository\PermissionResolver as APIPermissionResolver;
use Ibexa\Contracts\Core\Repository\PermissionService;
use Ibexa\Contracts\Core\Repository\Repository as RepositoryInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\LookupLimitationResult;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;

/**
 * Cache implementation of PermissionResolver and PermissionCriterionResolver interface.
 *
 * Implements both interfaces as the cached permission criterion lookup needs to be
 * expired when a different user is set as current users in the system.
 *
 * Cache is only done for content/read policy, as that is the one needed by search service.
 *
 * The logic here uses a cache TTL of a few seconds, as this is in-memory cache we are not
 * able to know if any other concurrent user might be changing permissions.
 */
class CachedPermissionService implements PermissionService
{
    private APIPermissionResolver $innerPermissionResolver;

    private APIPermissionCriterionResolver $permissionCriterionResolver;

    /** @var int */
    private $cacheTTL;

    /**
     * Counter for the current sudo nesting level {@see sudo()}.
     *
     * @var int
     */
    private $sudoNestingLevel = 0;

    /**
     * Cached value for current user's getCriterion() result.
     *
     * Value is null if not yet set or cleared.
     *
     * @var bool|\Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface|null
     */
    private $permissionCriterion;

    /**
     * Cache time stamp.
     *
     * @var int
     */
    private $permissionCriterionTs;

    /**
     * CachedPermissionService constructor.
     *
     * @param \Ibexa\Contracts\Core\Repository\PermissionResolver $innerPermissionResolver
     * @param \Ibexa\Contracts\Core\Repository\PermissionCriterionResolver $permissionCriterionResolver
     * @param int $cacheTTL By default set to 5 seconds, should be low to avoid to many permission exceptions on long running requests / processes (even if tolerant search service should handle that)
     */
    public function __construct(
        APIPermissionResolver $innerPermissionResolver,
        APIPermissionCriterionResolver $permissionCriterionResolver,
        int $cacheTTL = 5
    ) {
        $this->innerPermissionResolver = $innerPermissionResolver;
        $this->permissionCriterionResolver = $permissionCriterionResolver;
        $this->cacheTTL = $cacheTTL;
    }

    public function getCurrentUserReference(): UserReference
    {
        return $this->innerPermissionResolver->getCurrentUserReference();
    }

    public function setCurrentUserReference(UserReference $userReference): void
    {
        $this->permissionCriterion = null;
        $this->innerPermissionResolver->setCurrentUserReference($userReference);
    }

    public function hasAccess(string $module, string $function, ?UserReference $userReference = null)
    {
        return $this->innerPermissionResolver->hasAccess($module, $function, $userReference);
    }

    public function canUser(string $module, string $function, object $object, array $targets = []): bool
    {
        return $this->innerPermissionResolver->canUser($module, $function, $object, $targets);
    }

    /**
     * {@inheritdoc}
     */
    public function lookupLimitations(
        string $module,
        string $function,
        object $object,
        array $targets = [],
        array $limitationsIdentifiers = []
    ): LookupLimitationResult {
        return $this->innerPermissionResolver->lookupLimitations($module, $function, $object, $targets, $limitationsIdentifiers);
    }

    public function getPermissionsCriterion(string $module = 'content', string $function = 'read', ?array $targets = null)
    {
        // We only cache content/read lookup as those are the once frequently done, and it's only one we can safely
        // do that won't harm the system if it becomes stale (but user might experience permissions exceptions if it do)
        if ($module !== 'content' || $function !== 'read' || $this->sudoNestingLevel > 0) {
            return $this->permissionCriterionResolver->getPermissionsCriterion($module, $function, $targets);
        }

        if ($this->permissionCriterion !== null) {
            // If we are still within the cache TTL, then return the cached value
            if ((time() - $this->permissionCriterionTs) < $this->cacheTTL) {
                return $this->permissionCriterion;
            }
        }

        $this->permissionCriterionTs = time();
        $this->permissionCriterion = $this->permissionCriterionResolver->getPermissionsCriterion($module, $function, $targets);

        return $this->permissionCriterion;
    }

    /**
     * @internal For internal use only, do not depend on this method.
     */
    public function sudo(callable $callback, RepositoryInterface $outerRepository)
    {
        ++$this->sudoNestingLevel;
        try {
            $returnValue = $this->innerPermissionResolver->sudo($callback, $outerRepository);
        } catch (Exception $e) {
            --$this->sudoNestingLevel;
            throw $e;
        }
        --$this->sudoNestingLevel;

        return $returnValue;
    }

    public function getQueryPermissionsCriterion(): CriterionInterface
    {
        return $this->permissionCriterionResolver->getQueryPermissionsCriterion();
    }
}
