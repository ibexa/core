<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Abstract test case for spi cache impl, with in-memory handling.
 */
abstract class AbstractInMemoryCacheHandlerTestCase extends AbstractBaseHandlerTestCase
{
    abstract public function getHandlerMethodName(): string;

    abstract public function getHandlerClassName(): string;

    abstract public static function providerForUnCachedMethods(): array;

    /**
     * @param string $method
     * @param array $arguments
     * @param array|null $tagGeneratingArguments
     * @param array|null $keyGeneratingArguments
     * @param array|null $tags
     * @param array|null $key
     * @param mixed $returnValue
     */
    #[DataProvider('providerForUnCachedMethods')]
    final public function testUnCachedMethods(
        string $method,
        array $arguments,
        ?array $tagGeneratingArguments = null,
        ?array $keyGeneratingArguments = null,
        ?array $tags = null,
        ?array $key = null,
        $returnValue = null,
        bool $callInnerHandler = true
    ): void {
        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::once())->method('logCall');
        $this->loggerMock->expects(self::never())->method('logCacheHit');
        $this->loggerMock->expects(self::never())->method('logCacheMiss');

        $innerHandler = $this->createMock($this->getHandlerClassName());
        $this->persistenceHandlerMock
            ->expects($callInnerHandler ? self::once() : self::never())
            ->method($handlerMethodName)
            ->willReturn($innerHandler);

        $invocationMocker = $innerHandler
            ->expects($callInnerHandler ? self::once() : self::never())
            ->method($method)
            ->with(...$arguments);
        // workaround for mocking void-returning methods, null in this case denotes that, not null value
        if (null !== $returnValue) {
            $invocationMocker->willReturn($returnValue);
        }

        if ($tags || $key) {
            if ($tagGeneratingArguments) {
                $matcher = self::exactly(count($tagGeneratingArguments));
                $this->cacheIdentifierGeneratorMock
                    ->expects($matcher)
                    ->method('generateTag')
                    ->willReturnCallback(static function (...$parameters) use ($matcher, $tagGeneratingArguments, $tags): mixed {
                        self::assertEquals($tagGeneratingArguments[$matcher->numberOfInvocations() - 1], $parameters);

                        return $tags[$matcher->numberOfInvocations() - 1];
                    });
            }

            if ($keyGeneratingArguments) {
                $callsCount = count($keyGeneratingArguments);

                if (is_array($key)) {
                    $matcher = self::exactly($callsCount);
                    $this->cacheIdentifierGeneratorMock
                        ->expects($matcher)
                        ->method('generateKey')
                        ->willReturnCallback(static function (...$parameters) use ($matcher, $keyGeneratingArguments, $key): mixed {
                            self::assertEquals($keyGeneratingArguments[$matcher->numberOfInvocations() - 1], $parameters);

                            return $key[$matcher->numberOfInvocations() - 1];
                        });
                } else {
                    $this->cacheIdentifierGeneratorMock
                        ->expects(self::exactly($callsCount))
                        ->method('generateKey')
                        ->with($keyGeneratingArguments[0][0])
                        ->willReturn($key);
                }
            }

            $this->cacheMock
                ->expects(!empty($tags) ? self::once() : self::never())
                ->method('invalidateTags')
                ->with($tags);

            $this->cacheMock
                ->expects(!empty($key) ? self::once() : self::never())
                ->method('deleteItems')
                ->with($key);
        } else {
            $this->cacheMock
                ->expects(self::never())
                ->method(self::anything());
        }

        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $actualReturnValue = call_user_func_array([$handler, $method], $arguments);

        self::assertEquals($returnValue, $actualReturnValue);
    }

    abstract public static function providerForCachedLoadMethodsHit(): array;

    /**
     * @param string $method
     * @param array $arguments
     * @param string $key
     * @param array|null $tagGeneratingArguments
     * @param array|null $tagGeneratingResults
     * @param array|null $keyGeneratingArguments
     * @param array|null $keyGeneratingResults
     * @param mixed $data
     * @param bool $multi Default false, set to true if method will lookup several cache items.
     * @param array $additionalCalls Sets of additional calls being made to handlers, with 4 values (0: handler name, 1: handler class, 2: method, 3: return data)
     */
    #[DataProvider('providerForCachedLoadMethodsHit')]
    final public function testLoadMethodsCacheHit(
        string $method,
        array $arguments,
        string $key,
        ?array $tagGeneratingArguments = null,
        ?array $tagGeneratingResults = null,
        ?array $keyGeneratingArguments = null,
        ?array $keyGeneratingResults = null,
        $data = null,
        bool $multi = false,
        array $additionalCalls = []
    ): void {
        $cacheItem = $this->getCacheItem($key, $multi ? reset($data) : $data);
        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::once())->method('logCacheHit');
        $this->loggerMock->expects(self::never())->method('logCall');
        $this->loggerMock->expects(self::never())->method('logCacheMiss');

        if ($tagGeneratingArguments) {
            $matcher = self::exactly(count($tagGeneratingArguments));
            $this->cacheIdentifierGeneratorMock
                ->expects($matcher)
                ->method('generateTag')
                ->willReturnCallback(static function (...$parameters) use ($matcher, $tagGeneratingArguments, $tagGeneratingResults): mixed {
                    self::assertEquals($tagGeneratingArguments[$matcher->numberOfInvocations() - 1], $parameters);

                    return $tagGeneratingResults[$matcher->numberOfInvocations() - 1];
                });
        }

        if ($keyGeneratingArguments) {
            $matcher = self::exactly(count($keyGeneratingArguments));
            $this->cacheIdentifierGeneratorMock
                ->expects($matcher)
                ->method('generateKey')
                ->willReturnCallback(static function (...$parameters) use ($matcher, $keyGeneratingArguments, $keyGeneratingResults): mixed {
                    self::assertEquals($keyGeneratingArguments[$matcher->numberOfInvocations() - 1], $parameters);

                    return $keyGeneratingResults[$matcher->numberOfInvocations() - 1];
                });
        }

        if ($multi) {
            $this->cacheMock
                ->expects(self::once())
                ->method('getItems')
                ->with([$cacheItem->getKey()])
                ->willReturn([$key => $cacheItem]);
        } else {
            $this->cacheMock
                ->expects(self::once())
                ->method('getItem')
                ->with($cacheItem->getKey())
                ->willReturn($cacheItem);
        }

        $this->persistenceHandlerMock
            ->expects(self::never())
            ->method($handlerMethodName);

        foreach ($additionalCalls as $additionalCall) {
            $this->persistenceHandlerMock
                ->expects(self::never())
                ->method($additionalCall[0]);
        }

        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $return = call_user_func_array([$handler, $method], $arguments);

        self::assertEquals($data, $return);
    }

    abstract public static function providerForCachedLoadMethodsMiss(): array;

    /**
     * @param string $method
     * @param array $arguments
     * @param string $key
     * @param array|null $tagGeneratingArguments
     * @param array|null $tagGeneratingResults
     * @param array|null $keyGeneratingArguments
     * @param array|null $keyGeneratingResults
     * @param null $data
     * @param bool $multi Default false, set to true if method will lookup several cache items.
     * @param array $additionalCalls Sets of additional calls being made to handlers, with 4 values (0: handler name, 1: handler class, 2: method, 3: return data)
     */
    #[DataProvider('providerForCachedLoadMethodsMiss')]
    final public function testLoadMethodsCacheMiss(
        string $method,
        array $arguments,
        string $key,
        ?array $tagGeneratingArguments = null,
        ?array $tagGeneratingResults = null,
        ?array $keyGeneratingArguments = null,
        ?array $keyGeneratingResults = null,
        $data = null,
        bool $multi = false,
        array $additionalCalls = []
    ): void {
        $cacheItem = $this->getCacheItem($key, null);
        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::once())->method('logCacheMiss');
        $this->loggerMock->expects(self::never())->method('logCall');
        $this->loggerMock->expects(self::never())->method('logCacheHit');

        if ($tagGeneratingArguments) {
            $matcher = self::exactly(count($tagGeneratingArguments));
            $this->cacheIdentifierGeneratorMock
                ->expects($matcher)
                ->method('generateTag')
                ->willReturnCallback(static function (...$parameters) use ($matcher, $tagGeneratingArguments, $tagGeneratingResults): mixed {
                    self::assertEquals($tagGeneratingArguments[$matcher->numberOfInvocations() - 1], $parameters);

                    return $tagGeneratingResults[$matcher->numberOfInvocations() - 1];
                });
        }

        if ($keyGeneratingArguments) {
            $matcher = self::exactly(count($keyGeneratingArguments));
            $this->cacheIdentifierGeneratorMock
                ->expects($matcher)
                ->method('generateKey')
                ->willReturnCallback(static function (...$parameters) use ($matcher, $keyGeneratingArguments, $keyGeneratingResults): mixed {
                    self::assertEquals($keyGeneratingArguments[$matcher->numberOfInvocations() - 1], $parameters);

                    return $keyGeneratingResults[$matcher->numberOfInvocations() - 1];
                });
        }

        if ($multi) {
            $this->cacheMock
                ->expects(self::once())
                ->method('getItems')
                ->with([$cacheItem->getKey()])
                ->willReturn([$key => $cacheItem]);
        } else {
            $this->cacheMock
                ->expects(self::once())
                ->method('getItem')
                ->with($cacheItem->getKey())
                ->willReturn($cacheItem);
        }

        $innerHandlerMock = $this->createMock($this->getHandlerClassName());
        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method($handlerMethodName)
            ->willReturn($innerHandlerMock);

        $innerHandlerMock
            ->expects(self::once())
            ->method($method)
            ->with(...$arguments)
            ->willReturn($data);

        foreach ($additionalCalls as $additionalCall) {
            $innerHandlerMock = $this->createMock($additionalCall[1]);
            $this->persistenceHandlerMock
                ->expects(self::once())
                ->method($additionalCall[0])
                ->willReturn($innerHandlerMock);

            $innerHandlerMock
                ->expects(self::once())
                ->method($additionalCall[2])
                ->willReturn($additionalCall[3]);
        }

        $this->cacheMock
            ->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $return = call_user_func_array([$handler, $method], $arguments);

        self::assertEquals($data, $return);

        // Assert use of tags would probably need custom logic as internal property is [$tag => $tag] value, and we don't want to know that.
        //$this->assertAttributeEquals([], 'tags', $cacheItem);
    }
}
