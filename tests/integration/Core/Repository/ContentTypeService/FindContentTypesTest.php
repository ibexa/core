<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentTypeService;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContainsFieldDefinitionIds;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupIds;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\Identifiers;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\Ids;
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

        $contentTypes = $contentTypeService->findContentTypes($query);
        $identifiers = array_map(
            static fn (ContentType $contentType): string => $contentType->getIdentifier(),
            $contentTypes->items
        );

        self::assertCount(count($expectedIdentifiers), $identifiers);
        self::assertEqualsCanonicalizing($expectedIdentifiers, $identifiers);
    }

    public function testFindContentTypesAscSortedByIdentifier(): void
    {
        $contentTypeService = self::getContentTypeService();

        $contentTypes = $contentTypeService->findContentTypes(
            new ContentTypeQuery(
                new Identifiers(['folder', 'article', 'user', 'file']),
                [new Identifier()]
            ),
        );
        $identifiers = array_map(
            static fn (ContentType $contentType): string => $contentType->getIdentifier(),
            $contentTypes->items
        );

        self::assertCount(4, $identifiers);
        self::assertSame(['article', 'file', 'folder', 'user'], $identifiers);
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
                new ContainsFieldDefinitionIds([$fieldDefinitionToInclude->getId()]),
            )
        );

        self::assertCount(1, $contentTypes);
        self::assertSame('folder', $contentTypes->items[0]->getIdentifier());
    }

    /**
     * @return iterable<array{\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery, list<string>}>
     */
    public function dataProviderForTestFindContentTypes(): iterable
    {
        yield 'identifiers' => [
            new ContentTypeQuery(
                new Identifiers(['folder', 'article']),
            ),
            ['article', 'folder'],
        ];

        yield 'user group content type' => [
            new ContentTypeQuery(
                new ContentTypeGroupIds([2]),
            ),
            ['user', 'user_group'],
        ];

        yield 'ids' => [
            new ContentTypeQuery(
                new Ids([1]),
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
                    new Identifiers(['folder', 'article']),
                    new ContentTypeGroupIds([1]),
                ]),
            ),
            ['folder', 'article'],
        ];

        yield 'logical or' => [
            new ContentTypeQuery(
                new LogicalOr([
                    new Identifiers(['folder', 'article']),
                    new ContentTypeGroupIds([2]),
                ]),
            ),
            ['folder', 'article', 'user', 'user_group'],
        ];

        yield 'logical not resulting in empty set' => [
            new ContentTypeQuery(
                new LogicalAnd([
                    new LogicalNot([
                        new Identifiers(['user', 'user_group']),
                    ]),
                    new ContentTypeGroupIds([2]),
                ]),
            ),
            [],
        ];

        yield 'logical not' => [
            new ContentTypeQuery(
                new LogicalAnd([
                    new LogicalNot([
                        new Identifiers(['user']),
                    ]),
                    new ContentTypeGroupIds([2]),
                ]),
            ),
            ['user_group'],
        ];

        yield 'logical or outside with logical and inside' => [
            new ContentTypeQuery(
                new LogicalOr([
                    new LogicalAnd([
                        new Identifiers(['folder', 'article']),
                        new ContentTypeGroupIds([1]),
                    ]),
                    new Identifiers(['user']),
                ]),
            ),
            ['folder', 'article', 'user'],
        ];
    }
}
