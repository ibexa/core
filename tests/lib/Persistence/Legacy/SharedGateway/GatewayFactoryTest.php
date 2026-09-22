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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Traversable;

#[CoversClass(GatewayFactory::class)]
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
            Platforms\SQLitePlatform::class => new SqliteGateway(self::createStub(Connection::class)),
        ];

        $this->factory = new GatewayFactory(
            new FallbackGateway(self::createStub(Connection::class)),
            $gateways,
        );
    }

    /**
     * @param \Doctrine\DBAL\Connection $connectionMock
     * @param string $expectedInstance
     *
     * @throws \Doctrine\DBAL\Exception
     */
    #[DataProvider('getTestBuildSharedGatewayData')]
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
    public static function getTestBuildSharedGatewayData(): Traversable
    {
        $databasePlatformGatewayPairs = [
            [new Platforms\SQLitePlatform(), SqliteGateway::class],
            [new Platforms\MySQL80Platform(), FallbackGateway::class],
            [new Platforms\MySQLPlatform(), FallbackGateway::class],
            [new Platforms\PostgreSQLPlatform(), FallbackGateway::class],
        ];

        foreach ($databasePlatformGatewayPairs as $databasePlatformGatewayPair) {
            [$databasePlatform, $sharedGateway] = $databasePlatformGatewayPair;
            $connectionMock = self::createStub(Connection::class);
            $connectionMock
                ->method('getDatabasePlatform')
                ->willReturn($databasePlatform);

            yield [
                $connectionMock,
                $sharedGateway,
            ];
        }
    }
}
