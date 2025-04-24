<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Core\Persistence\Cache\PersistenceLogger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Cache\PersistenceLogger
 */
class PersistenceLoggerTest extends TestCase
{
    protected PersistenceLogger $logger;

    /**
     * Set up the HandlerTest.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new PersistenceLogger();
    }

    protected function tearDown(): void
    {
        unset($this->logger);
        parent::tearDown();
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
    public function testGetStats(PersistenceLogger $logger): PersistenceLogger
    {
        self::assertEquals(4, $logger->getStats()['uncached']);

        return $logger;
    }

    /**
     * @depends testGetStats
     */
    public function testGetCallValues(PersistenceLogger $logger): void
    {
        $calls = $logger->getCalls();
        // As we don't care about the hash index, we get the array values instead
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
}
