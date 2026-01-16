<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Cache\EventSubscriber;

use Ibexa\Contracts\Core\Repository\Events\Trash\RecoverEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\TrashEvent;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RoleAssignmentCacheInvalidationListener implements EventSubscriberInterface
{
    private const ROLE_ASSIGNMENT_ROLE_LIST_IDENTIFIER = 'role_assignment_role_list';

    private TagAwareAdapterInterface $cache;

    private CacheIdentifierGeneratorInterface $identifierGenerator;

    private RoleService $roleService;

    private UserService $userService;

    public function __construct(
        TagAwareAdapterInterface $cache,
        CacheIdentifierGeneratorInterface $identifierGenerator,
        RoleService $roleService,
        UserService $userService
    ) {
        $this->cache = $cache;
        $this->identifierGenerator = $identifierGenerator;
        $this->roleService = $roleService;
        $this->userService = $userService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TrashEvent::class => 'onTrashContent',
            RecoverEvent::class => 'onRecoverContent',
        ];
    }

    public function onRecoverContent(RecoverEvent $event): void
    {
        $this->clearCache($event->getTrashItem());
    }

    public function onTrashContent(TrashEvent $event): void
    {
        $item = $event->getTrashItem();

        if ($item !== null) {
            $this->clearCache($item);
        }
    }

    private function clearCache(Location $item): void
    {
        $tags = $this->buildCacheTags($item);

        if (!empty($tags)) {
            $this->cache->invalidateTags($tags);
        }
    }

    /**
     * @return array<string>
     */
    private function buildCacheTags(Location $item): array
    {
        $contentId = $item->getContentId();

        try {
            $userGroup = $this->userService->loadUserGroup($contentId);
            $roleAssignments = $this->roleService->getRoleAssignmentsForUserGroup($userGroup);
        } catch (APINotFoundException $e) {
            try {
                $user = $this->userService->loadUser($contentId);
                $roleAssignments = $this->roleService->getRoleAssignmentsForUser($user, true);
            } catch (APINotFoundException $e) {
                return [];
            }
        }

        $tags = [];
        foreach ($roleAssignments as $roleAssignment) {
            $tags[] = $this->identifierGenerator->generateTag(
                self::ROLE_ASSIGNMENT_ROLE_LIST_IDENTIFIER,
                [$roleAssignment->getRole()->id]
            );
        }

        return $tags;
    }
}
