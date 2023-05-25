<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Mapper;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Repository\Mapper\SearchHitMapperInterface;
use Ibexa\Core\Repository\Mapper\SearchHitMapperRegistry;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\TestCase;

final class SearchResultMapperRegistryTest extends TestCase
{
    public function testHasMapper(): void
    {
        $registry = new SearchHitMapperRegistry([
            $this->getSearchResultMapperMock(),
        ]);

        $searchHit = new SearchHit([
            'valueObject' => new Content(),
        ]);

        self::assertTrue($registry->hasMapper($searchHit));
    }

    public function testDoesntHaveMapper(): void
    {
        $registry = new SearchHitMapperRegistry([]);
        $searchHit = new SearchHit([
            'valueObject' => new Location(),
        ]);

        self::assertFalse($registry->hasMapper($searchHit));
    }

    public function testGetMapper(): void
    {
        $exampleMapper = $this->getSearchResultMapperMock();
        $searchHit = new SearchHit([
            'valueObject' => new Content(),
        ]);

        $registry = new SearchHitMapperRegistry([
            $exampleMapper,
        ]);

        self::assertSame($exampleMapper, $registry->getMapper($searchHit));
    }

    public function testGetMapperThrowsInvalidArgumentException(): void
    {
        $message = "Argument '\$hit' is invalid: undefined Ibexa\Core\Repository\Mapper\SearchHitMapperInterface for search hit Ibexa\Core\Repository\Values\Content\Location";

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $registry = new SearchHitMapperRegistry([/* Empty registry */]);
        $registry->getMapper(
            new SearchHit([
                'valueObject' => new Location(),
            ])
        );
    }

    private function getSearchResultMapperMock(): SearchHitMapperInterface
    {
        $searchHit = new SearchHit([
            'valueObject' => new Content(),
        ]);

        $mock = $this->createMock(SearchHitMapperInterface::class);
        $mock
            ->method('supports')
            ->with($searchHit)
            ->willReturn(true);

        return $mock;
    }
}
