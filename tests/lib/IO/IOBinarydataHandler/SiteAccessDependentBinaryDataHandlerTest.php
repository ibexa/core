<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\IOBinarydataHandler;

use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\IOBinarydataHandler\SiteAccessDependentBinaryDataHandler;
use Ibexa\Tests\Core\IO\Stub\RangeAwareBinarydataHandler;
use PHPUnit\Framework\TestCase;

final class SiteAccessDependentBinaryDataHandlerTest extends TestCase
{
    private const HANDLER_NAME = 'default';

    public function testGetResourceRangeDelegatesToRangeAwareHandler(): void
    {
        $resource = fopen('php://memory', 'rb');
        $innerHandler = $this->createMock(RangeAwareBinarydataHandler::class);
        $innerHandler
            ->expects(self::once())
            ->method('getResourceRange')
            ->with('my/file.mp4', 10, 5)
            ->willReturn($resource);

        self::assertSame($resource, $this->createHandler($innerHandler)->getResourceRange('my/file.mp4', 10, 5));
    }

    public function testGetResourceRangeReturnsNullForNonRangeAwareHandler(): void
    {
        $innerHandler = $this->createMock(IOBinarydataHandler::class);
        $innerHandler
            ->expects(self::never())
            ->method('getResource');

        self::assertNull($this->createHandler($innerHandler)->getResourceRange('my/file.mp4', 10, 5));
    }

    private function createHandler(IOBinarydataHandler $innerHandler): SiteAccessDependentBinaryDataHandler
    {
        $configResolver = $this->createStub(ConfigResolverInterface::class);
        $configResolver->method('getParameter')->willReturn(self::HANDLER_NAME);

        $registry = new HandlerRegistry();
        $registry->setHandlersMap([self::HANDLER_NAME => $innerHandler]);

        return new SiteAccessDependentBinaryDataHandler($configResolver, $registry);
    }
}
