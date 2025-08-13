<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentTypeService;

use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupIds;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\Identifiers;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\Ids;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\IsSystem;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalNot;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalOr;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentTypeService
 */
final class CountContentTypesTest extends RepositoryTestCase
{
    public function testCountContentTypesWithNullQuery(): void
    {
        $contentTypeService = self::getContentTypeService();

        $contentTypesCount = $contentTypeService->countContentTypes();

        $contentTypesObjects = $contentTypeService->findContentTypes(new ContentTypeQuery(null, [], 0, 999));

        self::assertSame($contentTypesObjects->totalCount, $contentTypesCount);
    }

    /**
     * @dataProvider dataProviderForTestCount
     */
    public function testCountContentTypes(ContentTypeQuery $query, int $expectedCount): void
    {
        $contentTypeService = self::getContentTypeService();

        $count = $contentTypeService->countContentTypes($query);

        self::assertSame($expectedCount, $count);
    }

    /**
     * @return iterable<array{\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery, int}>
     */
    public function dataProviderForTestCount(): iterable
    {
        yield 'identifiers' => [
            new ContentTypeQuery(
                new Identifiers(['folder', 'article']),
            ),
            2,
        ];

        yield 'user group content type' => [
            new ContentTypeQuery(
                new ContentTypeGroupIds([2]),
            ),
            2,
        ];

        yield 'ids' => [
            new ContentTypeQuery(
                new Ids([1]),
            ),
            1,
        ];

        yield 'system group' => [
            new ContentTypeQuery(
                new IsSystem(false),
            ),
            3,
        ];

        yield 'logical and' => [
            new ContentTypeQuery(
                new LogicalAnd([
                    new Identifiers(['folder', 'article']),
                    new ContentTypeGroupIds([1]),
                ]),
            ),
            2,
        ];

        yield 'logical or' => [
            new ContentTypeQuery(
                new LogicalOr([
                    new Identifiers(['folder', 'article']),
                    new ContentTypeGroupIds([2]),
                ]),
            ),
            4,
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
            0,
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
            1,
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
            3,
        ];
    }
}
