<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\Decorator\SearchServiceDecorator;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchServiceDecoratorTest extends TestCase
{
    protected function createDecorator(MockObject $service): SearchService
    {
        return new class($service) extends SearchServiceDecorator {
        };
    }

    protected function createServiceMock(): MockObject
    {
        return $this->createMock(SearchService::class);
    }

    public function testFindContentDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Query::class),
            ['random_value_5ced05ce17d631.27870175'],
            true,
        ];

        $serviceMock->expects(self::once())->method('findContent')->with(...$parameters);

        $decoratedService->findContent(...$parameters);
    }

    public function testFindContentInfoDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Query::class),
            ['random_value_5ced05ce17d6d9.76060657'],
            true,
        ];

        $serviceMock->expects(self::once())->method('findContentInfo')->with(...$parameters);

        $decoratedService->findContentInfo(...$parameters);
    }

    public function testFindSingleDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Criterion::class),
            ['random_value_5ced05ce17ef80.90204500'],
            true,
        ];

        $serviceMock->expects(self::once())->method('findSingle')->with(...$parameters);

        $decoratedService->findSingle(...$parameters);
    }

    public function testSuggestDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            'random_value_5ced05ce17f030.62511430',
            ['random_value_5ced05ce17f044.48777415'],
            10,
            $this->createMock(Criterion::class),
        ];

        $serviceMock->expects(self::once())->method('suggest')->with(...$parameters);

        $decoratedService->suggest(...$parameters);
    }

    public function testFindLocationsDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(LocationQuery::class),
            ['random_value_5ced05ce17f647.36429312'],
            true,
        ];

        $serviceMock->expects(self::once())->method('findLocations')->with(...$parameters);

        $decoratedService->findLocations(...$parameters);
    }

    /**
     * @dataProvider getSearchEngineCapabilities
     *
     * @param int $capability
     */
    public function testSupportsDecorator(int $capability): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $serviceMock->expects(self::once())->method('supports')->with($capability);

        $decoratedService->supports($capability);
    }

    /**
     * Data provider for testSupportsDecorator.
     *
     * @see testSupportsDecorator
     *
     * @return array
     */
    public function getSearchEngineCapabilities(): array
    {
        return [
            [SearchService::CAPABILITY_SCORING],
            [SearchService::CAPABILITY_CUSTOM_FIELDS],
            [SearchService::CAPABILITY_SPELLCHECK],
            [SearchService::CAPABILITY_HIGHLIGHT],
            [SearchService::CAPABILITY_SUGGEST],
            [SearchService::CAPABILITY_ADVANCED_FULLTEXT],
        ];
    }
}
