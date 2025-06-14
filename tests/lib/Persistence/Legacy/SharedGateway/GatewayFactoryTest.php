<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\SharedGateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms;
use Ibexa\Core\Persistence\Legacy\SharedGateway\DatabasePlatform\FallbackGateway;
use Ibexa\Core\Persistence\Legacy\SharedGateway\DatabasePlatform\SqliteGateway;
use Ibexa\Core\Persistence\Legacy\SharedGateway\GatewayFactory;
use PHPUnit\Framework\TestCase;
use Traversable;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\SharedGateway\GatewayFactory
 */
final class GatewayFactoryTest extends TestCase
{
    /** @var \Ibexa\Core\Persistence\Legacy\SharedGateway\GatewayFactory */
    private $factory;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function setUp(): void
    {
        $gateways = [
            Platforms\SqlitePlatform::class => new SqliteGateway($this->createMock(Connection::class)),
        ];

        $this->factory = new GatewayFactory(
            new FallbackGateway($this->createMock(Connection::class)),
            $gateways,
        );
    }

    /**
     * @dataProvider getTestBuildSharedGatewayData
     *
     * @param \Doctrine\DBAL\Connection $connectionMock
     * @param string $expectedInstance
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testBuildSharedGateway(
        Connection $connectionMock,
        string $expectedInstance
    ): void {
        self::assertInstanceOf(
            $expectedInstance,
            $this->factory->buildSharedGateway($connectionMock)
        );
    }

    /**
     * @return \Doctrine\DBAL\Connection[]|\PHPUnit\Framework\MockObject\MockObject[]|\Traversable
     */
    public function getTestBuildSharedGatewayData(): Traversable
    {
        $databasePlatformGatewayPairs = [
            [new Platforms\SqlitePlatform(), SqliteGateway::class],
            [new Platforms\MySQL80Platform(), FallbackGateway::class],
            [new Platforms\MySQLPlatform(), FallbackGateway::class],
            [new Platforms\PostgreSQLPlatform(), FallbackGateway::class],
        ];

        foreach ($databasePlatformGatewayPairs as $databasePlatformGatewayPair) {
            [$databasePlatform, $sharedGateway] = $databasePlatformGatewayPair;
            /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $databasePlatform */
            $connectionMock = $this
                ->createMock(Connection::class);
            $connectionMock
                ->expects(self::any())
                ->method('getDatabasePlatform')
                ->willReturn($databasePlatform);

            yield [
                $connectionMock,
                $sharedGateway,
            ];
        }
    }
}
