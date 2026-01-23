<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Core\Persistence\Cache\PersistenceLogger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Cache\PersistenceLogger
 */
class PersistenceLoggerTest extends TestCase
{
    protected PersistenceLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new PersistenceLogger();
    }

    public function testGetName(): void
    {
        self::assertEquals(PersistenceLogger::NAME, $this->logger->getName());
    }

    public function testGetCalls(): void
    {
        self::assertEquals([], $this->logger->getCalls());
    }

    public function testLogCall(): PersistenceLogger
    {
        $this->logger->logCall(__METHOD__);
        $this->logger->logCall(__METHOD__);
        $this->logger->logCall(__METHOD__);
        $this->logger->logCall(__METHOD__, [33]);

        return $this->logger;
    }

    /**
     * @depends testLogCall
     */
    public function testGetCallValues(PersistenceLogger $logger): void
    {
        $calls = $logger->getCalls();
        // As we don't care about the hash index we get the array values instead
        $calls = array_values($calls);

        $method = __CLASS__ . '::testLogCall';

        self::assertEquals($method, $calls[0]['method']);
        self::assertEquals([], $calls[0]['arguments']);
        self::assertCount(1, $calls[0]['traces']);
        self::assertEquals(['uncached' => 3, 'miss' => 0, 'hit' => 0, 'memory' => 0], $calls[0]['stats']);

        self::assertEquals($method, $calls[1]['method']);
        self::assertEquals([33], $calls[1]['arguments']);
        self::assertCount(1, $calls[1]['traces']);
        self::assertEquals(['uncached' => 1, 'miss' => 0, 'hit' => 0, 'memory' => 0], $calls[1]['stats']);
    }

    public function testLogCacheHit(): void
    {
        self::assertSame([], $this->logger->getCalls());
        $this->logger->logCacheHit();
        self::assertSame(
            $this->buildExpectedCallTrace('d4371e7c', __METHOD__, 0, 1),
            $this->logger->getCalls()
        );
    }

    public function testLogCacheMiss(): void
    {
        self::assertSame([], $this->logger->getCalls());
        $this->logger->logCacheMiss();
        self::assertSame(
            $this->buildExpectedCallTrace('f4051ef3', __METHOD__, 1, 0),
            $this->logger->getCalls()
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildExpectedCallTrace(string $callHash, string $method, int $miss, int $hit): array
    {
        return [
            $callHash => [
                'method' => $method,
                'arguments' => [],
                'stats' => [
                    'uncached' => 0,
                    'miss' => $miss,
                    'hit' => $hit,
                    'memory' => 0,
                ],
                'traces' => [
                    '0e1c1b51' => [
                        'trace' => [
                            0 => 'PHPUnit\\Framework\\TestCase->runTest()',
                            1 => 'PHPUnit\\Framework\\TestCase->runBare()',
                        ],
                        'count' => 1,
                    ],
                ],
            ],
        ];
    }
}
