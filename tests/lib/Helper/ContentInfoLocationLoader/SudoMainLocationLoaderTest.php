<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Helper\ContentInfoLocationLoader;

use Ibexa\Contracts\Core\Persistence\User\Handler as SPIUserHandler;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Helper\ContentInfoLocationLoader\SudoMainLocationLoader;
use Ibexa\Core\Repository\Mapper\RoleDomainMapper;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\Permission\PermissionResolver;
use Ibexa\Core\Repository\Repository;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\TestCase;

class SudoMainLocationLoaderTest extends TestCase
{
    /** @var \Ibexa\Core\Helper\ContentInfoLocationLoader\SudoMainLocationLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->loader = new SudoMainLocationLoader($this->getRepositoryMock());
    }

    public function testLoadLocationNoMainLocation()
    {
        $this->expectException(NotFoundException::class);

        $contentInfo = new ContentInfo(['id' => 123]);

        $this->getLocationServiceMock()
            ->expects(self::never())
            ->method('loadLocation');

        $this->loader->loadLocation($contentInfo);
    }

    public function testLoadLocation()
    {
        $contentInfo = new ContentInfo([
            'id' => 123,
            'mainLocationId' => 42,
        ]);
        $location = new Location(['id' => 42]);

        $this->getRepositoryMock()
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->will(self::returnValue($this->getPermissionResolverMock()));

        $this->getRepositoryMock()
            ->expects(self::any())
            ->method('getLocationService')
            ->will(self::returnValue($this->getLocationServiceMock()));

        $this->getLocationServiceMock()
            ->expects(self::once())
            ->method('loadLocation')
            ->with(42)
            ->will(self::returnValue($location));

        self::assertSame($location, $this->loader->loadLocation($contentInfo));
    }

    public function testLoadLocationError()
    {
        $this->expectException(NotFoundException::class);

        $contentInfo = new ContentInfo(['id' => 123, 'mainLocationId' => 42]);
        $location = new Location(['id' => 42]);

        $this->getRepositoryMock()
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->will(self::returnValue($this->getPermissionResolverMock()));

        $this->getRepositoryMock()
            ->expects(self::any())
            ->method('getLocationService')
            ->will(self::returnValue($this->getLocationServiceMock()));

        $this->getLocationServiceMock()
            ->expects(self::once())
            ->method('loadLocation')
            ->with(42)
            ->will(
                self::throwException(new NotFoundException('main location of content', 42))
            );

        self::assertSame($location, $this->loader->loadLocation($contentInfo));
    }

    /**
     * @return \Ibexa\Core\Repository\Repository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getRepositoryMock()
    {
        static $repositoryMock;

        if ($repositoryMock === null) {
            $repositoryClass = Repository::class;

            $repositoryMock = $this
                ->getMockBuilder($repositoryClass)
                ->disableOriginalConstructor()
                ->setMethods(
                    array_diff(
                        get_class_methods($repositoryClass),
                        ['sudo']
                    )
                )
                ->getMock();
        }

        return $repositoryMock;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\LocationService|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getLocationServiceMock()
    {
        static $mock;

        if ($mock === null) {
            $mock = $this
                ->getMockBuilder(LocationService::class)
                ->getMock();
        }

        return $mock;
    }

    /**
     * @return \Ibexa\Core\Repository\Permission\PermissionResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getPermissionResolverMock()
    {
        $configResolverMock = $this->createMock(ConfigResolverInterface::class);
        $configResolverMock
            ->method('getParameter')
            ->with('anonymous_user_id')
            ->willReturn(10);

        return $this
            ->getMockBuilder(PermissionResolver::class)
            ->setMethods(null)
            ->setConstructorArgs(
                [
                    $this
                        ->getMockBuilder(RoleDomainMapper::class)
                        ->disableOriginalConstructor()
                        ->getMock(),
                    $this
                        ->getMockBuilder(LimitationService::class)
                        ->getMock(),
                    $this
                        ->getMockBuilder(SPIUserHandler::class)
                        ->getMock(),
                    $configResolverMock,
                    [],
                ]
            )
            ->getMock();
    }
}
