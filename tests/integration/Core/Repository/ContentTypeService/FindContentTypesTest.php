<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentTypeService;

use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContainsFieldDefinitionIds;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupIds;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\Identifiers;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\IsSystem;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalOr;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentTypeService
 */
final class FindContentTypesTest extends RepositoryTestCase
{
    public function testFindContentTypesWithNullQueryFinds25Results(): void
    {
        $contentTypeService = self::getContentTypeService();

        $contentTypes = $contentTypeService->findContentTypes();

        self::assertCount(25, $contentTypes);
    }

    public function testFindContentTypesWithIdentifiersCriterion(): void
    {
        $contentTypeService = self::getContentTypeService();

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery([
                new Identifiers(['folder', 'article']),
            ])
        );

        self::assertCount(2, $contentTypes);

        $contentTypesGroupedByIdentifier = [];
        foreach ($contentTypes as $contentType) {
            $contentTypesGroupedByIdentifier[$contentType->identifier] = $contentType;
        }

        self::assertSame('folder', $contentTypesGroupedByIdentifier['folder']->identifier);
        self::assertSame('article', $contentTypesGroupedByIdentifier['article']->identifier);
    }

    public function testFindContentTypesContainingFieldDefinitions(): void
    {
        $contentTypeService = self::getContentTypeService();
        $folderContentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $fieldDefinitionToInclude = null;
        foreach ($folderContentType->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition->getIdentifier() === 'short_name') {
                $fieldDefinitionToInclude = $fieldDefinition;
            }
        }

        assert($fieldDefinitionToInclude !== null);

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery([
                new ContainsFieldDefinitionIds([$fieldDefinitionToInclude->getId()]),
            ])
        );

        self::assertCount(1, $contentTypes);
        self::assertSame('folder', $contentTypes[0]->getIdentifier());
    }

    public function testFindContentTypesByGroupIdentifiers(): void
    {
        $contentTypeService = self::getContentTypeService();
        $usersContentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Users');

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery([
                new ContentTypeGroupIds([$usersContentTypeGroup->id]),
            ])
        );

        $contentTypesGroupedByIdentifier = [];
        foreach ($contentTypes as $contentType) {
            $contentTypesGroupedByIdentifier[$contentType->getIdentifier()] = $contentType;
        }

        self::assertCount(2, $contentTypes);
        self::assertSame('user', $contentTypesGroupedByIdentifier['user']->getIdentifier());
        self::assertSame('user_group', $contentTypesGroupedByIdentifier['user_group']->getIdentifier());
    }

    public function testFindContentTypesSystemGroup(): void
    {
        $contentTypeService = self::getContentTypeService();

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery([
                new IsSystem(false),
            ])
        );

        foreach ($contentTypes as $contentType) {
            $group = $contentType->getContentTypeGroups()[0];
            self::assertFalse($group->isSystem);
        }
    }

    public function testFindContentTypesLogicalAnd(): void
    {
        $contentTypeService = self::getContentTypeService();
        $contentContentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery([
                new LogicalAnd([
                    new Identifiers(['folder', 'article']),
                    new ContentTypeGroupIds([$contentContentTypeGroup->id]),
                ]),
            ])
        );

        $contentTypesGroupedByIdentifier = [];
        foreach ($contentTypes as $contentType) {
            $contentTypesGroupedByIdentifier[$contentType->getIdentifier()] = $contentType;
        }

        self::assertCount(2, $contentTypes);
        self::assertEqualsCanonicalizing(['folder', 'article'], array_keys($contentTypesGroupedByIdentifier));
    }

    public function testFindContentTypesWithLogicalOrContainingLogicalAnd(): void
    {
        $contentTypeService = self::getContentTypeService();
        $contentContentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery([
                new LogicalOr([
                    new LogicalAnd([
                        new Identifiers(['folder', 'article']),
                        new ContentTypeGroupIds([$contentContentTypeGroup->id]),
                    ]),
                    new Identifiers(['user']),
                ]),
            ])
        );

        $contentTypesGroupedByIdentifier = [];
        foreach ($contentTypes as $contentType) {
            $contentTypesGroupedByIdentifier[$contentType->getIdentifier()] = $contentType;
        }

        self::assertCount(3, $contentTypes);
        self::assertEqualsCanonicalizing(['folder', 'article', 'user'], array_keys($contentTypesGroupedByIdentifier));
    }

    public function testFindContentTypesAscSortedByIdentifier(): void
    {
        $contentTypeService = self::getContentTypeService();

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery([
                new Identifiers(['folder', 'article', 'user', 'file']),
            ])
        );

        $contentTypesGroupedByIdentifier = [];
        foreach ($contentTypes as $contentType) {
            $contentTypesGroupedByIdentifier[$contentType->getIdentifier()] = $contentType;
        }

        self::assertCount(4, $contentTypes);
        self::assertSame(['article', 'file', 'folder', 'user'], array_keys($contentTypesGroupedByIdentifier));
    }
}
