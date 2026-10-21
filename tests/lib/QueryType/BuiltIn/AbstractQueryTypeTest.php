<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\QueryType\BuiltIn;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\QueryType\BuiltIn\SortClausesFactoryInterface;
use Ibexa\Core\QueryType\QueryType;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

abstract class AbstractQueryTypeTest extends TestCase
{
    protected const ROOT_LOCATION_ID = 2;
    protected const ROOT_LOCATION_PATH_STRING = '/1/2/';

    /** @var Repository&Stub */
    private $repository;

    /** @var ConfigResolverInterface&Stub */
    private $configResolver;

    /** @var SortClausesFactoryInterface&Stub */
    private $sortClausesFactory;

    /** @var QueryType */
    private $queryType;

    protected function setUp(): void
    {
        $rootLocation = new Location([
            'id' => self::ROOT_LOCATION_ID,
            'pathString' => self::ROOT_LOCATION_PATH_STRING,
        ]);

        $locationService = $this->createStub(LocationService::class);
        $locationService
            ->method('loadLocation')
            ->willReturnMap([
                [self::ROOT_LOCATION_ID, null, null, $rootLocation],
            ]);

        $this->repository = $this->createStub(Repository::class);
        $this->repository->method('getLocationService')->willReturn($locationService);
        $this->repository
            ->method('sudo')
            ->willReturnCallback(fn (callable $callback) => $callback($this->repository));

        $this->configResolver = $this->createStub(ConfigResolverInterface::class);
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['content.tree_root.location_id', null, null, self::ROOT_LOCATION_ID],
            ]);

        $this->sortClausesFactory = $this->createStub(SortClausesFactoryInterface::class);

        $this->queryType = $this->createQueryType(
            $this->repository,
            $this->configResolver,
            $this->sortClausesFactory
        );
    }

    /**
     * @dataProvider dataProviderForGetQuery
     */
    final public function testGetQuery(
        array $parameters,
        Query $expectedQuery
    ): void {
        $this->assertEquals($expectedQuery, $this->queryType->getQuery($parameters));
    }

    final public function testGetName(): void
    {
        $this->assertEquals(
            $this->getExpectedName(),
            $this->queryType->getName()
        );
    }

    final public function testGetSupportedParameters(): void
    {
        $this->assertEqualsCanonicalizing(
            $this->getExpectedSupportedParameters(),
            $this->queryType->getSupportedParameters()
        );
    }

    abstract public function dataProviderForGetQuery(): iterable;

    abstract protected function createQueryType(
        Repository $repository,
        ConfigResolverInterface $configResolver,
        SortClausesFactoryInterface $sortClausesFactory
    ): QueryType;

    abstract protected function getExpectedName(): string;

    abstract protected function getExpectedSupportedParameters(): array;
}

class_alias(AbstractQueryTypeTest::class, 'eZ\Publish\Core\QueryType\BuiltIn\Tests\AbstractQueryTypeTest');
