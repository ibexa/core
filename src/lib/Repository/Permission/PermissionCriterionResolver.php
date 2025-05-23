<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Permission;

use Ibexa\Contracts\Core\Repository\PermissionCriterionResolver as APIPermissionCriterionResolver;
use Ibexa\Contracts\Core\Repository\PermissionResolver as PermissionResolverInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalOr;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Core\Limitation\TargetOnlyLimitationType;
use RuntimeException;

/**
 * Implementation of Permissions Criterion Resolver.
 */
class PermissionCriterionResolver implements APIPermissionCriterionResolver
{
    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    private $innerPermissionResolver;

    /** @var \Ibexa\Core\Repository\Permission\LimitationService */
    private $limitationService;

    /**
     * Constructor.
     *
     * @param \Ibexa\Contracts\Core\Repository\PermissionResolver $innerPermissionResolver
     * @param \Ibexa\Core\Repository\Permission\LimitationService $limitationService
     */
    public function __construct(
        PermissionResolverInterface $innerPermissionResolver,
        LimitationService $limitationService
    ) {
        $this->innerPermissionResolver = $innerPermissionResolver;
        $this->limitationService = $limitationService;
    }

    /**
     * Get permission criteria if needed and return false if no access at all.
     *
     * @uses \Ibexa\Contracts\Core\Repository\PermissionResolver::getCurrentUserReference()
     * @uses \Ibexa\Contracts\Core\Repository\PermissionResolver::hasAccess()
     */
    public function getPermissionsCriterion(string $module = 'content', string $function = 'read', ?array $targets = null)
    {
        $permissionSets = $this->innerPermissionResolver->hasAccess($module, $function);
        if (is_bool($permissionSets)) {
            return $permissionSets;
        }

        if (empty($permissionSets)) {
            throw new RuntimeException("Received an empty array of limitations from hasAccess( '{$module}', '{$function}' )");
        }

        /*
         * RoleAssignment is a OR condition, so is policy, while limitations is a AND condition
         *
         * If RoleAssignment has limitation then policy OR conditions are wrapped in a AND condition with the
         * role limitation, otherwise it will be merged into RoleAssignment's OR condition.
         */
        $currentUserRef = $this->innerPermissionResolver->getCurrentUserReference();
        $roleAssignmentOrCriteria = [];
        foreach ($permissionSets as $permissionSet) {
            // $permissionSet is a RoleAssignment, but in the form of role limitation & role policies hash
            $policyOrCriteria = [];
            /** @var \Ibexa\Contracts\Core\Repository\Values\User\Policy */
            foreach ($permissionSet['policies'] as $policy) {
                $limitations = $policy->getLimitations();
                if (empty($limitations)) {
                    // Given policy gives full access, optimize away all role policies (but not role limitation if any)
                    // This should be optimized on create/update of Roles, however we keep this here for bc with older data
                    $policyOrCriteria = [];
                    break;
                }

                $limitationsAndCriteria = [];
                foreach ($limitations as $limitation) {
                    $limitationsAndCriteria[] = $this->getCriterionForLimitation($limitation, $currentUserRef, $targets);
                }

                $policyOrCriteria[] = isset($limitationsAndCriteria[1]) ?
                    new LogicalAnd($limitationsAndCriteria) :
                    $limitationsAndCriteria[0];
            }

            /**
             * Apply role limitations if there is one.
             *
             * @var \Ibexa\Contracts\Core\Repository\Values\User\Limitation[]
             */
            if ($permissionSet['limitation'] instanceof Limitation) {
                // We need to match both the limitation AND *one* of the policies, aka; roleLimit AND policies(OR)
                if (!empty($policyOrCriteria)) {
                    $criterion = $this->getCriterionForLimitation($permissionSet['limitation'], $currentUserRef, $targets);
                    $roleAssignmentOrCriteria[] = new LogicalAnd(
                        [
                            $criterion,
                            isset($policyOrCriteria[1]) ? new LogicalOr($policyOrCriteria) : $policyOrCriteria[0],
                        ]
                    );
                } else {
                    $roleAssignmentOrCriteria[] = $this->getCriterionForLimitation(
                        $permissionSet['limitation'],
                        $currentUserRef,
                        $targets
                    );
                }
            } elseif (!empty($policyOrCriteria)) {
                // Otherwise merge $policyOrCriteria into $roleAssignmentOrCriteria
                // There is no role limitation, so any of the policies can globally match in the returned OR criteria
                $roleAssignmentOrCriteria = empty($roleAssignmentOrCriteria) ?
                    $policyOrCriteria :
                    array_merge($roleAssignmentOrCriteria, $policyOrCriteria);
            }
        }

        if (empty($roleAssignmentOrCriteria)) {
            return false;
        }

        return isset($roleAssignmentOrCriteria[1]) ?
            new LogicalOr($roleAssignmentOrCriteria) :
            $roleAssignmentOrCriteria[0];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $currentUserRef
     * @param array|null $targets
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface|\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalOperator
     */
    private function getCriterionForLimitation(Limitation $limitation, UserReference $currentUserRef, ?array $targets): CriterionInterface
    {
        $type = $this->limitationService->getLimitationType($limitation->getIdentifier());
        if ($type instanceof TargetOnlyLimitationType) {
            return $type->getCriterionByTarget($limitation, $currentUserRef, $targets);
        }

        return $type->getCriterion($limitation, $currentUserRef);
    }

    public function getQueryPermissionsCriterion(): CriterionInterface
    {
        // Permission Criterion handling work-around to avoid rewriting old architecture of perm. sys.
        $permissionCriterion = $this->getPermissionsCriterion(
            'content',
            'read'
        );
        if (true === $permissionCriterion) {
            return new Criterion\MatchAll();
        }
        if (false === $permissionCriterion) {
            return new Criterion\MatchNone();
        }

        return $permissionCriterion;
    }
}
