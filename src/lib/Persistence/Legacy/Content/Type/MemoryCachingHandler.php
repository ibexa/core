<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\CreateStruct as GroupCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct as GroupUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as BaseContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\UpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache;

class MemoryCachingHandler implements BaseContentTypeHandler
{
    private const CONTENT_TYPE_GROUP_LIST = 'content_type_group_list';
    private const CONTENT_TYPE_GROUP = 'content_type_group';
    private const BY_IDENTIFIER_SUFFIX = 'by_identifier_suffix';
    private const CONTENT_TYPE_GROUP_LIST_BY_GROUP = 'content_type_list_by_group';
    private const CONTENT_TYPE = 'content_type';
    private const BY_REMOTE_SUFFIX = 'by_remote_suffix';
    private const CONTENT_TYPE_LIST_BY_GROUP = 'content_type_list_by_group';
    private const CONTENT_TYPE_FIELD_MAP = 'content_type_field_map';
    private const CONTENT_TYPE_LIST_BY_FIELD_DEFINITION_IDENTIFIER = 'content_type_list_by_field_definition_identifier';

    /** Inner handler to dispatch calls to. */
    protected BaseContentTypeHandler $innerHandler;

    protected InMemoryCache $cache;

    private CacheIdentifierGeneratorInterface $generator;

    public function __construct(
        BaseContentTypeHandler $handler,
        InMemoryCache $cache,
        CacheIdentifierGeneratorInterface $generator
    ) {
        $this->innerHandler = $handler;
        $this->cache = $cache;
        $this->generator = $generator;
    }

    public function createGroup(GroupCreateStruct $createStruct): Group
    {
        $group = $this->innerHandler->createGroup($createStruct);
        $this->storeGroupCache([$group]);
        $this->cache->deleteMulti([
            $this->generator->generateKey(self::CONTENT_TYPE_GROUP_LIST, [], true),
        ]);

        return $group;
    }

    public function updateGroup(GroupUpdateStruct $struct): Group
    {
        $group = $this->innerHandler->updateGroup($struct);
        $this->storeGroupCache([$group]);
        $this->cache->deleteMulti([
            $this->generator->generateKey(self::CONTENT_TYPE_GROUP_LIST, [], true),
        ]);

        return $group;
    }

    public function deleteGroup($groupId): void
    {
        $this->innerHandler->deleteGroup($groupId);
        // Delete by primary key will remove the object, so we don't need to clear `-by-identifier` variant here.
        $this->cache->deleteMulti([
            $this->generator->generateKey(self::CONTENT_TYPE_GROUP, [$groupId], true),
            $this->generator->generateKey(self::CONTENT_TYPE_GROUP_LIST, [], true),
        ]);
    }

    public function loadGroup($groupId): Group
    {
        $group = $this->cache->get(
            $this->generator->generateKey(self::CONTENT_TYPE_GROUP, [$groupId], true)
        );

        if ($group === null) {
            $group = $this->innerHandler->loadGroup($groupId);
            $this->storeGroupCache([$group]);
        }

        return $group;
    }

    /**
     * @return Group[]
     */
    public function loadGroups(array $groupIds): array
    {
        $groups = $missingIds = [];
        foreach ($groupIds as $groupId) {
            $group = $this->cache->get(
                $this->generator->generateKey(self::CONTENT_TYPE_GROUP, [$groupId], true)
            );

            if ($group !== null) {
                $groups[$groupId] = $group;
            } else {
                $missingIds[] = $groupId;
            }
        }

        if (!empty($missingIds)) {
            $loaded = $this->innerHandler->loadGroups($missingIds);
            $this->storeGroupCache($loaded);
            /** @noinspection AdditionOperationOnArraysInspection */
            $groups += $loaded;
        }

        return $groups;
    }

    public function loadGroupByIdentifier($identifier): Group
    {
        $group = $this->cache->get(
            $this->generator->generateKey(self::CONTENT_TYPE_GROUP, [$identifier], true) .
            $this->generator->generateKey(self::BY_IDENTIFIER_SUFFIX)
        );

        if ($group === null) {
            $group = $this->innerHandler->loadGroupByIdentifier($identifier);
            $this->storeGroupCache([$group]);
        }

        return $group;
    }

    /**
     * @return Group[]
     */
    public function loadAllGroups(): array
    {
        $contentTypeGroupListKey = $this->generator->generateKey(self::CONTENT_TYPE_GROUP_LIST, [], true);
        $groups = $this->cache->get($contentTypeGroupListKey);

        if ($groups === null) {
            $groups = $this->innerHandler->loadAllGroups();
            $this->storeGroupCache($groups, $contentTypeGroupListKey);
        }

        return $groups;
    }

    /**
     * @return Type[]
     */
    public function loadContentTypes(
        $groupId,
        $status = Type::STATUS_DEFINED
    ): array {
        if ($status !== Type::STATUS_DEFINED) {
            return $this->innerHandler->loadContentTypes($groupId, $status);
        }

        $contentTypeGroupListByGroup = $this->generator->generateKey(self::CONTENT_TYPE_GROUP_LIST_BY_GROUP, [$groupId], true);
        $types = $this->cache->get($contentTypeGroupListByGroup);

        if ($types === null) {
            $types = $this->innerHandler->loadContentTypes($groupId, $status);
            $this->storeTypeCache($types, $contentTypeGroupListByGroup);
        }

        return $types;
    }

    public function findContentTypes(
        ?ContentTypeQuery $query = null,
        array $prioritizedLanguages = []
    ): array {
        return $this->innerHandler->findContentTypes($query, $prioritizedLanguages);
    }

    /**
     * @return Type[]
     */
    public function loadContentTypeList(array $contentTypeIds): array
    {
        $contentTypes = $missingIds = [];
        foreach ($contentTypeIds as $contentTypeId) {
            $cacheKey = $this->generator->generateKey(
                self::CONTENT_TYPE,
                [$contentTypeId],
                true
            ) . '-' . Type::STATUS_DEFINED;

            $cacheItem = $this->cache->get($cacheKey);
            if ($cacheItem !== null) {
                $contentTypes[$contentTypeId] = $cacheItem;
            } else {
                $missingIds[] = $contentTypeId;
            }
        }

        if (!empty($missingIds)) {
            $loaded = $this->innerHandler->loadContentTypeList($missingIds);
            $this->storeTypeCache($loaded);
            /** @noinspection AdditionOperationOnArraysInspection */
            $contentTypes += $loaded;
        }

        return $contentTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function loadContentTypesByFieldDefinitionIdentifier(string $identifier): array
    {
        $cacheKey = $this->generator->generateKey(
            self::CONTENT_TYPE_LIST_BY_FIELD_DEFINITION_IDENTIFIER,
            [$identifier],
            true
        );

        $types = $this->cache->get($cacheKey);
        if ($types === null) {
            $types = $this->innerHandler->loadContentTypesByFieldDefinitionIdentifier($identifier);
            $this->storeTypeCache($types, $cacheKey);
        }

        return $types;
    }

    /**
     * {@inheritdoc}
     */
    public function load(
        $contentTypeId,
        $status = Type::STATUS_DEFINED
    ) {
        $contentType = $this->cache->get(
            $this->generator->generateKey(self::CONTENT_TYPE, [$contentTypeId], true) . '-' . $status
        );

        if ($contentType === null) {
            $contentType = $this->innerHandler->load($contentTypeId, $status);
            $this->storeTypeCache([$contentType]);
        }

        return $contentType;
    }

    public function loadByIdentifier($identifier): Type
    {
        $contentType = $this->cache->get(
            $this->generator->generateKey(self::CONTENT_TYPE, [$identifier], true) .
            $this->generator->generateKey(self::BY_IDENTIFIER_SUFFIX)
        );

        if ($contentType === null) {
            $contentType = $this->innerHandler->loadByIdentifier($identifier);
            $this->storeTypeCache([$contentType]);
        }

        return $contentType;
    }

    public function loadByRemoteId($remoteId): Type
    {
        $contentType = $this->cache->get(
            $this->generator->generateKey(self::CONTENT_TYPE, [$remoteId], true) .
            $this->generator->generateKey(self::BY_REMOTE_SUFFIX)
        );

        if ($contentType === null) {
            $contentType = $this->innerHandler->loadByRemoteId($remoteId);
            $this->storeTypeCache([$contentType]);
        }

        return $contentType;
    }

    public function create(CreateStruct $createStruct): Type
    {
        $contentType = $this->innerHandler->create($createStruct);
        // Don't store as FieldTypeConstraints is not setup fully here from Legacy SE side
        $this->deleteTypeCache($contentType->id, $contentType->status);

        return $contentType;
    }

    public function update(
        $typeId,
        $status,
        UpdateStruct $contentType
    ): Type {
        $contentType = $this->innerHandler->update($typeId, $status, $contentType);
        $this->storeTypeCache([$contentType]);

        return $contentType;
    }

    public function delete(
        $contentTypeId,
        $status
    ): void {
        $this->innerHandler->delete($contentTypeId, $status);
        $this->deleteTypeCache($contentTypeId, $status);
    }

    public function createDraft(
        $modifierId,
        $contentTypeId
    ): Type {
        $contentType = $this->innerHandler->createDraft($modifierId, $contentTypeId);
        // Don't store as FieldTypeConstraints is not setup fully here from Legacy SE side
        $this->deleteTypeCache($contentType->id, $contentType->status);

        return $contentType;
    }

    public function copy(
        $userId,
        $contentTypeId,
        $status
    ): Type {
        $contentType = $this->innerHandler->copy($userId, $contentTypeId, $status);
        $this->storeTypeCache([$contentType]);

        return $contentType;
    }

    public function unlink(
        $groupId,
        $contentTypeId,
        $status
    ): bool {
        $keys = [
            $this->generator->generateKey(self::CONTENT_TYPE, [$contentTypeId], true) . '-' . $status,
        ];

        if ($status === Type::STATUS_DEFINED) {
            $keys[] = $this->generator->generateKey(self::CONTENT_TYPE_LIST_BY_GROUP, [$groupId], true);
        }

        $this->cache->deleteMulti($keys);

        return $this->innerHandler->unlink($groupId, $contentTypeId, $status);
    }

    public function link(
        $groupId,
        $contentTypeId,
        $status
    ): bool {
        $keys = [
            $this->generator->generateKey(self::CONTENT_TYPE, [$contentTypeId], true) . '-' . $status,
        ];

        if ($status === Type::STATUS_DEFINED) {
            $keys[] = $this->generator->generateKey(self::CONTENT_TYPE_LIST_BY_GROUP, [$groupId], true);
        }

        $this->cache->deleteMulti($keys);

        return $this->innerHandler->link($groupId, $contentTypeId, $status);
    }

    public function getFieldDefinition(
        $id,
        $status
    ): FieldDefinition {
        return $this->innerHandler->getFieldDefinition($id, $status);
    }

    public function getContentCount($contentTypeId): int
    {
        return $this->innerHandler->getContentCount($contentTypeId);
    }

    public function addFieldDefinition(
        $contentTypeId,
        $status,
        FieldDefinition $fieldDefinition
    ) {
        $this->deleteTypeCache($contentTypeId, $status);

        return $this->innerHandler->addFieldDefinition($contentTypeId, $status, $fieldDefinition);
    }

    public function removeFieldDefinition(
        int $contentTypeId,
        int $status,
        FieldDefinition $fieldDefinition
    ): void {
        $this->deleteTypeCache($contentTypeId, $status);
        $this->innerHandler->removeFieldDefinition($contentTypeId, $status, $fieldDefinition);
    }

    public function updateFieldDefinition(
        $contentTypeId,
        $status,
        FieldDefinition $fieldDefinition
    ): void {
        $this->deleteTypeCache($contentTypeId, $status);

        $this->innerHandler->updateFieldDefinition($contentTypeId, $status, $fieldDefinition);
    }

    public function publish($contentTypeId): void
    {
        $this->clearCache();

        $this->innerHandler->publish($contentTypeId);
    }

    /**
     * @return array<mixed>
     */
    public function getSearchableFieldMap(): array
    {
        $mapCacheKey = $this->generator->generateKey(self::CONTENT_TYPE_FIELD_MAP, [], true);
        $map = $this->cache->get($mapCacheKey);

        if ($map === null) {
            $map = $this->innerHandler->getSearchableFieldMap();

            $this->cache->setMulti(
                $map,
                static function (): array {
                    return [];
                },
                $mapCacheKey
            );
        }

        return $map;
    }

    public function removeContentTypeTranslation(
        int $contentTypeId,
        string $languageCode
    ): Type {
        $this->clearCache();

        return $this->innerHandler->removeContentTypeTranslation($contentTypeId, $languageCode);
    }

    /**
     * Clear internal caches.
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    protected function deleteTypeCache(
        int $contentTypeId,
        int $status = Type::STATUS_DEFINED
    ): void {
        if ($status !== Type::STATUS_DEFINED) {
            // Delete by primary key will remove the object, so we don't need to clear other variants here.
            $this->cache->deleteMulti([
                $this->generator->generateKey(self::CONTENT_TYPE, [$contentTypeId], true) . '-' . $status,
                $this->generator->generateKey(self::CONTENT_TYPE_FIELD_MAP, [], true),
            ]);
        } else {
            // We don't know group id in order to clear relevant "ez-content-type-list-by-group-$groupId".
            $this->cache->clear();
        }
    }

    protected function storeTypeCache(
        array $types,
        ?string $listIndex = null
    ): void {
        $this->cache->setMulti(
            $types,
            function (Type $type): array {
                if ($type->status !== Type::STATUS_DEFINED) {
                    return [
                        $this->generator->generateKey(self::CONTENT_TYPE, [$type->id], true) . '-' . $type->status,
                    ];
                }

                return [
                    $this->generator->generateKey(self::CONTENT_TYPE, [$type->id], true) . '-' . $type->status,

                    $this->generator->generateKey(self::CONTENT_TYPE, [$type->identifier], true) . '-' . $type->status .
                    $this->generator->generateKey(self::BY_IDENTIFIER_SUFFIX),

                    $this->generator->generateKey(self::CONTENT_TYPE, [$type->remoteId], true) . '-' . $type->status .
                    $this->generator->generateKey(self::BY_REMOTE_SUFFIX),
                ];
            },
            $listIndex
        );

        $this->cache->deleteMulti([
            $this->generator->generateKey(self::CONTENT_TYPE_FIELD_MAP, [], true),
        ]);
    }

    protected function storeGroupCache(
        array $groups,
        ?string $listIndex = null
    ): void {
        $this->cache->setMulti(
            $groups,
            function (Group $group): array {
                return [
                    $this->generator->generateKey(self::CONTENT_TYPE_GROUP, [$group->id], true),
                    $this->generator->generateKey(self::CONTENT_TYPE_GROUP, [$group->identifier], true) .
                    $this->generator->generateKey(self::BY_IDENTIFIER_SUFFIX),
                ];
            },
            $listIndex
        );
    }

    public function deleteByUserAndStatus(
        int $userId,
        int $status
    ): void {
        $this->innerHandler->deleteByUserAndStatus($userId, $status);
    }
}
