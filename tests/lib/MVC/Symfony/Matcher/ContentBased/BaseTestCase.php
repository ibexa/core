<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased;

use Ibexa\Contracts\Core\Persistence\User\Handler as SPIUserHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\View\Provider\Configured;
use Ibexa\Core\Repository\Mapper\RoleDomainMapper;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\Permission\PermissionResolver;
use Ibexa\Core\Repository\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected Repository & MockObject $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = $this->getRepositoryMock();
    }

    /**
     * @param array<mixed> $matchingConfig
     */
    protected function getPartiallyMockedViewProvider(array $matchingConfig = []): Configured & MockObject
    {
        return $this
            ->getMockBuilder(Configured::class)
            ->setConstructorArgs(
                [
                    $this->repositoryMock,
                    $matchingConfig,
                ]
            )
            ->onlyMethods(['getMatcher'])
            ->getMock();
    }

    protected function getRepositoryMock(): Repository & MockObject
    {
        $repositoryMock = $this->createMock(Repository::class);

        $repositoryMock->method('sudo')->willReturnCallback(
            static function (callable $callback) use ($repositoryMock): mixed {
                return $callback($repositoryMock);
            }
        );

        return $repositoryMock;
    }

    /**
     * @param array<mixed> $properties
     */
    protected function getLocationMock(array $properties = []): Location & MockObject
    {
        return $this
            ->getMockBuilder(Location::class)
            ->setConstructorArgs([$properties])
            ->getMockForAbstractClass();
    }

    /**
     * @param array<mixed> $properties
     */
    protected function getContentInfoMock(array $properties = []): ContentInfo & MockObject
    {
        return $this->
            getMockBuilder(ContentInfo::class)
            ->setConstructorArgs([$properties])
            ->getMockForAbstractClass();
    }

    protected function getPermissionResolverMock(): PermissionResolver & MockObject
    {
        $configResolverMock = $this->createMock(ConfigResolverInterface::class);
        $configResolverMock
            ->method('getParameter')
            ->with('anonymous_user_id')
            ->willReturn(10);

        return $this
            ->getMockBuilder(PermissionResolver::class)
            ->setConstructorArgs(
                [
                    $this->createMock(RoleDomainMapper::class),
                    $this->createMock(LimitationService::class),
                    $this->createMock(SPIUserHandler::class),
                    $configResolverMock,
                    [],
                ]
            )
            ->getMock();
    }
}
