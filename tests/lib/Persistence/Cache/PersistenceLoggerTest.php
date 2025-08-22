<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Core\Persistence\Cache\PersistenceLogger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Cache\PersistenceLogger::getName
 */
class PersistenceLoggerTest extends TestCase
{
    /** @var \Ibexa\Core\Persistence\Cache\PersistenceLogger */
    protected $logger;

    /**
     * Setup the HandlerTest.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new PersistenceLogger();
    }

    public function testGetName()
    {
        self::assertEquals(PersistenceLogger::NAME, $this->logger->getName());
    }

    public function testGetCalls()
    {
        self::assertEquals([], $this->logger->getCalls());
    }

    public function testLogCall()
    {
        self::assertNull($this->logger->logCall(__METHOD__));
        $this->logger->logCall(__METHOD__);
        $this->logger->logCall(__METHOD__);
        $this->logger->logCall(__METHOD__, [33]);

        return $this->logger;
    }

    /**
     * @depends testGetCountValues
     *
     * @param \Ibexa\Core\Persistence\Cache\PersistenceLogger $logger
     */
    public function testGetCallValues($logger)
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
}
