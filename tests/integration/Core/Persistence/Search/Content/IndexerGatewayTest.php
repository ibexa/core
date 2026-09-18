<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Persistence\Search\Content;

use DateTimeImmutable;
use Ibexa\Core\Search\Legacy\Content\IndexerGateway;
use Ibexa\Tests\Integration\Core\BaseGatewayTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
#[CoversClass(IndexerGateway::class)]
final class IndexerGatewayTest extends BaseGatewayTestCase
{
    /** @var \Ibexa\Core\Search\Legacy\Content\IndexerGateway */
    private $gateway;

    /**
     * @throws \ErrorException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new IndexerGateway($this->getRawDatabaseConnection());
    }

    public static function getDataForContentSince(): iterable
    {
        yield '1999-01-01' => [
            new DateTimeImmutable('1999-01-01'),
            9,
            2,
        ];

        yield 'now' => [
            new DateTimeImmutable('now'),
            0,
            2,
        ];
    }

    public static function getDataForContentInSubtree(): iterable
    {
        yield '/1/5/' => [
            '/1/5/',
            8,
            1,
        ];

        yield '/999/888/' => [
            '/999/888/',
            0,
            1,
        ];
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[DataProvider('getDataForContentSince')]
    public function testGetContentSince(
        DateTimeImmutable $since,
        int $expectedCount,
        int $iterationCount
    ): void {
        self::assertCount($expectedCount, iterator_to_array($this->gateway->getContentSince($since, $iterationCount)));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[DataProvider('getDataForContentSince')]
    public function testCountContentSince(
        DateTimeImmutable $since,
        int $expectedCount,
        int $iterationCount
    ): void {
        self::assertSame(
            $expectedCount * $iterationCount,
            $this->gateway->countContentSince($since)
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[DataProvider('getDataForContentInSubtree')]
    public function testGetContentInSubtree(
        string $subtreePath,
        int $expectedCount,
        int $iterationCount
    ): void {
        self::assertCount(
            $expectedCount,
            iterator_to_array($this->gateway->getContentInSubtree($subtreePath, $iterationCount))
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[DataProvider('getDataForContentInSubtree')]
    public function testCountContentInSubtree(
        string $subtreePath,
        int $expectedCount,
        int $iterationCount
    ): void {
        self::assertSame(
            $expectedCount * $iterationCount,
            $this->gateway->countContentInSubtree($subtreePath)
        );
    }

    public function testCountAllContent(): void
    {
        self::assertCount(9, iterator_to_array($this->gateway->getAllContent(2)));
    }

    public function testGetAllContent(): void
    {
        self::assertSame(18, $this->gateway->countAllContent());
    }
}
