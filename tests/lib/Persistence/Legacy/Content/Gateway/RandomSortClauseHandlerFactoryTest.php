<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Factory\RandomSortClauseHandlerFactory;
use PHPUnit\Framework\TestCase;

class RandomSortClauseHandlerFactoryTest extends TestCase
{
    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    public function testGetGateway()
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $goodGateway = $this->createMock(AbstractRandom::class);
        $goodGateway
            ->method('supportsPlatform')
            ->with($platform)
            ->willReturn(true);

        $badGateway = $this->createMock(AbstractRandom::class);
        $badGateway
            ->method('supportsPlatform')
            ->with($platform)
            ->willReturn(false);

        $handlerFactory = new RandomSortClauseHandlerFactory($connection, [$badGateway, $goodGateway]);

        self::assertSame($goodGateway, $handlerFactory->getGateway());
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    public function testGetGatewayNotImplemented()
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $badGateway1 = $this->createMock(AbstractRandom::class);
        $badGateway1
            ->method('supportsPlatform')
            ->with($platform)
            ->willReturn(false);

        $badGateway2 = $this->createMock(AbstractRandom::class);
        $badGateway2
            ->method('supportsPlatform')
            ->with($platform)
            ->willReturn(false);

        $handlerFactory = new RandomSortClauseHandlerFactory($connection, [$badGateway1, $badGateway2]);

        self::expectException(InvalidArgumentException::class);
        $handlerFactory->getGateway();
    }
}
