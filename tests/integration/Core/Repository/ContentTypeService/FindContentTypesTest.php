<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentTypeService;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContainsFieldDefinitionId;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupId;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeId;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\IsSystem;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalNot;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalOr;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\SortClause\Identifier;
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

    /**
     * @param list<string> $expectedIdentifiers
     *
     * @dataProvider dataProviderForTestFindContentTypes
     */
    public function testFindContentTypes(ContentTypeQuery $query, array $expectedIdentifiers): void
    {
        $contentTypeService = self::getContentTypeService();

        $expectedCount = $contentTypeService->findContentTypes(
            new ContentTypeQuery(
                null,
                [],
                0,
                0,
            )
        );
        $expectedCount = count($expectedCount->getContentTypes());

        $contentTypes = $contentTypeService->findContentTypes($query);

        $identifiers = array_map(
            static fn (ContentType $contentType): string => $contentType->getIdentifier(),
            $contentTypes->getContentTypes(),
        );

        self::assertCount(count($expectedIdentifiers), $identifiers);
        self::assertEqualsCanonicalizing($expectedIdentifiers, $identifiers);
        self::assertSame($expectedCount, $contentTypes->getTotalCount());
    }

    public function testFindContentTypesAscSortedByIdentifier(): void
    {
        $contentTypeService = self::getContentTypeService();

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery(
                new ContentTypeIdentifier(['folder', 'article', 'user', 'file']),
                [new Identifier()]
            ),
        );
        $identifiers = array_map(
            static fn (ContentType $contentType): string => $contentType->getIdentifier(),
            $contentTypes->getContentTypes()
        );

        self::assertCount(4, $identifiers);
        self::assertSame(['article', 'file', 'folder', 'user'], $identifiers);
    }

    public function testPagination(): void
    {
        $contentTypeService = self::getContentTypeService();

        $collectedContentTypeIDs = [];
        $pageSize = 10;
        $noOfPages = 3;

        for ($offset = 0; $offset < $noOfPages; $offset += $pageSize) {
            $searchResult = $contentTypeService->findContentTypes(
                new ContentTypeQuery(null, [], $offset, $pageSize),
            );

            // an actual number of items on a current page
            self::assertCount($pageSize, $searchResult);

            // check if results are not duplicated across multiple pages
            foreach ($searchResult->getContentTypes() as $contentType) {
                self::assertNotContains(
                    $contentType->getIdentifier(),
                    $collectedContentTypeIDs,
                    "Content type '{$contentType->getIdentifier()}' exists on multiple pages"
                );
                $collectedContentTypeIDs[] = $contentType->getIdentifier();
            }
        }
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
            new ContentTypeQuery(
                new ContainsFieldDefinitionId([$fieldDefinitionToInclude->getId()]),
            )
        );

        self::assertCount(1, $contentTypes);
        self::assertSame('folder', $contentTypes->getContentTypes()[0]->getIdentifier());
    }

    /**
     * @return iterable<array{\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery, list<string>}>
     */
    public function dataProviderForTestFindContentTypes(): iterable
    {
        yield 'identifiers' => [
            new ContentTypeQuery(
                new ContentTypeIdentifier(['folder', 'article']),
            ),
            ['article', 'folder'],
        ];

        yield 'single identifier' => [
            new ContentTypeQuery(
                new ContentTypeIdentifier('folder'),
            ),
            ['folder'],
        ];

        yield 'user group' => [
            new ContentTypeQuery(
                new ContentTypeGroupId([2]),
            ),
            ['user', 'user_group'],
        ];

        yield 'single user group' => [
            new ContentTypeQuery(
                new ContentTypeGroupId(2),
            ),
            ['user', 'user_group'],
        ];

        yield 'ids' => [
            new ContentTypeQuery(
                new ContentTypeId([1]),
            ),
            ['folder'],
        ];

        yield 'single id' => [
            new ContentTypeQuery(
                new ContentTypeId(1),
            ),
            ['folder'],
        ];

        yield 'system group' => [
            new ContentTypeQuery(
                new IsSystem(false),
            ),
            ['folder', 'user', 'user_group'],
        ];

        yield 'logical and' => [
            new ContentTypeQuery(
                new LogicalAnd([
                    new ContentTypeIdentifier(['folder', 'article']),
                    new ContentTypeGroupId([1]),
                ]),
            ),
            ['folder', 'article'],
        ];

        yield 'logical or' => [
            new ContentTypeQuery(
                new LogicalOr([
                    new ContentTypeIdentifier(['folder', 'article']),
                    new ContentTypeGroupId([2]),
                ]),
            ),
            ['folder', 'article', 'user', 'user_group'],
        ];

        yield 'logical not resulting in empty set' => [
            new ContentTypeQuery(
                new LogicalAnd([
                    new LogicalNot([
                        new ContentTypeIdentifier(['user', 'user_group']),
                    ]),
                    new ContentTypeGroupId([2]),
                ]),
            ),
            [],
        ];

        yield 'logical not' => [
            new ContentTypeQuery(
                new LogicalAnd([
                    new LogicalNot([
                        new ContentTypeIdentifier(['user']),
                    ]),
                    new ContentTypeGroupId([2]),
                ]),
            ),
            ['user_group'],
        ];

        yield 'logical or outside with logical and inside' => [
            new ContentTypeQuery(
                new LogicalOr([
                    new LogicalAnd([
                        new ContentTypeIdentifier(['folder', 'article']),
                        new ContentTypeGroupId([1]),
                    ]),
                    new ContentTypeIdentifier(['user']),
                ]),
            ),
            ['folder', 'article', 'user'],
        ];
    }
}
