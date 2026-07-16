<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository;

use Ibexa\Contracts\Core\Persistence\Filter\Location\Handler as LocationFilteringHandler;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions\Exception;
use Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface;
use Ibexa\Contracts\Core\Repository\PermissionCriterionResolver;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Core\Repository\LocationService;
use Ibexa\Core\Repository\Mapper\ContentDomainMapper;
use PHPUnit\Framework\TestCase;

final class LocationServiceTest extends TestCase
{
    /** @var \Ibexa\Contracts\Core\Repository\LocationService */
    private $locationService;

    protected function setUp(): void
    {
        $this->locationService = new LocationService(
            $this->createMock(Repository::class),
            $this->createMock(PersistenceHandler::class),
            $this->createMock(ContentDomainMapper::class),
            $this->createMock(NameSchemaServiceInterface::class),
            $this->createMock(PermissionCriterionResolver::class),
            $this->createMock(PermissionResolver::class),
            $this->createMock(LocationFilteringHandler::class),
            $this->createMock(ContentTypeService::class)
        );
    }

    /**
     * @throws Exception
     */
    public function testFindDoesNotModifyFilter(): void
    {
        $filter = new Filter();
        $originalFilter = clone $filter;
        $this->locationService->find($filter, ['eng-GB']);
        self::assertEquals($originalFilter, $filter);
    }

    public function testCountDoesNotModifyFilter(): void
    {
        $filter = new Filter();
        $originalFilter = clone $filter;
        $this->locationService->count($filter, ['eng-GB']);
        self::assertEquals($originalFilter, $filter);
    }
}
