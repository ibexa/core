<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Core\Persistence\Cache\Adapter\TransactionalInMemoryCacheAdapter;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * Test case to verify Integration tests are setup with the right instances.
 */
class EnvTest extends BaseTestCase
{
    /**
     * Verify Redis cache is setup if asked for, if not file system.
     */
    public function testVerifyCacheDriver()
    {
        $pool = $this->getSetupFactory()->getServiceContainer()->get('ibexa.cache_pool');

        self::assertInstanceOf(TransactionalInMemoryCacheAdapter::class, $pool);

        $reflectionDecoratedPool = new \ReflectionProperty($pool, 'sharedPool');
        $reflectionDecoratedPool->setAccessible(true);
        $pool = $reflectionDecoratedPool->getValue($pool);

        self::assertInstanceOf(TagAwareAdapter::class, $pool);

        $reflectionPool = new \ReflectionProperty($pool, 'pool');
        $reflectionPool->setAccessible(true);
        $innerPool = $reflectionPool->getValue($pool);

        if (getenv('CUSTOM_CACHE_POOL') === 'singleredis') {
            self::assertInstanceOf(RedisAdapter::class, $innerPool);
        } else {
            self::assertInstanceOf(ArrayAdapter::class, $innerPool);
        }
    }
}
