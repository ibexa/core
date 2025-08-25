<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

/**
 * Abstract test case for spi cache impl.
 */
abstract class AbstractCacheHandlerTestCase extends AbstractBaseHandlerTestCase
{
    abstract public function getHandlerMethodName(): string;

    abstract public function getHandlerClassName(): string;

    abstract public function providerForUnCachedMethods(): array;

    /**
     * @dataProvider providerForUnCachedMethods
     *
     * @param string $method
     * @param array $arguments
     * @param array|null $tagGeneratingArguments
     * @param array|null $keyGeneratingArguments
     * @param array|null $tags
     * @param string|array|null $key
     * @param mixed $returnValue
     */
    final public function testUnCachedMethods(
        string $method,
        array $arguments,
        ?array $tagGeneratingArguments = null,
        ?array $keyGeneratingArguments = null,
        ?array $tags = null,
        $key = null,
        $returnValue = null
    ) {
        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::once())->method('logCall');
        $this->loggerMock->expects(self::never())->method('logCacheHit');
        $this->loggerMock->expects(self::never())->method('logCacheMiss');

        $innerHandler = $this->createMock($this->getHandlerClassName());
        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method($handlerMethodName)
            ->willReturn($innerHandler);

        $invocationMocker = $innerHandler
            ->expects(self::once())
            ->method($method)
            ->with(...$arguments);
        // workaround for mocking void-returning methods, null in this case denotes that, not null value
        if (null !== $returnValue) {
            $invocationMocker->willReturn($returnValue);
        }

        if ($tags || $key) {
            if ($tagGeneratingArguments) {
                $this->cacheIdentifierGeneratorMock
                    ->expects(self::exactly(count($tagGeneratingArguments)))
                    ->method('generateTag')
                    ->withConsecutive(...$tagGeneratingArguments)
                    ->willReturnOnConsecutiveCalls(...$tags);
            }

            if ($keyGeneratingArguments) {
                $callsCount = count($keyGeneratingArguments);

                if (is_array($key)) {
                    $this->cacheIdentifierGeneratorMock
                        ->expects(self::exactly($callsCount))
                        ->method('generateKey')
                        ->withConsecutive(...$keyGeneratingArguments)
                        ->willReturnOnConsecutiveCalls(...$key);
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
                ->expects(!empty($key) && is_string($key) ? self::once() : self::never())
                ->method('deleteItem')
                ->with($key);

            $this->cacheMock
                ->expects(!empty($key) && is_array($key) ? self::once() : self::never())
                ->method('deleteItems')
                ->with($key);
        }

        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $actualReturnValue = call_user_func_array([$handler, $method], $arguments);

        self::assertEquals($returnValue, $actualReturnValue);
    }

    abstract public function providerForCachedLoadMethodsHit(): array;

    /**
     * @dataProvider providerForCachedLoadMethodsHit
     *
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
    ) {
        $cacheItem = $this->getCacheItem($key, $multi ? reset($data) : $data);
        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::never())->method('logCall');

        if ($tagGeneratingArguments) {
            $this->cacheIdentifierGeneratorMock
                ->expects(self::exactly(count($tagGeneratingArguments)))
                ->method('generateTag')
                ->withConsecutive(...$tagGeneratingArguments)
                ->willReturnOnConsecutiveCalls(...$tagGeneratingResults);
        }

        if ($keyGeneratingArguments) {
            $this->cacheIdentifierGeneratorMock
                ->expects(self::exactly(count($keyGeneratingArguments)))
                ->method('generateKey')
                ->withConsecutive(...$keyGeneratingArguments)
                ->willReturnOnConsecutiveCalls(...$keyGeneratingResults);
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

    abstract public function providerForCachedLoadMethodsMiss(): array;

    /**
     * @dataProvider providerForCachedLoadMethodsMiss
     *
     * @param string $method
     * @param array $arguments
     * @param string $key
     * @param array|null $tagGeneratingArguments
     * @param array|null $tagGeneratingResults
     * @param array|null $keyGeneratingArguments
     * @param array|null $keyGeneratingResults
     * @param object $data
     * @param bool $multi Default false, set to true if method will lookup several cache items.
     * @param array $additionalCalls Sets of additional calls being made to handlers, with 4 values (0: handler name, 1: handler class, 2: method, 3: return data)
     */
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
    ) {
        $cacheItem = $this->getCacheItem($key, null);
        $handlerMethodName = $this->getHandlerMethodName();

        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $this->loggerMock
            ->expects(self::once())
            ->method('logCall')
            ->with(get_class($handler) . '::' . $method, self::isType('array'));

        if ($tagGeneratingArguments) {
            $this->cacheIdentifierGeneratorMock
                ->expects(self::exactly(count($tagGeneratingArguments)))
                ->method('generateTag')
                ->withConsecutive(...$tagGeneratingArguments)
                ->willReturnOnConsecutiveCalls(...$tagGeneratingResults);
        }

        if ($keyGeneratingArguments) {
            $this->cacheIdentifierGeneratorMock
                ->expects(self::exactly(count($keyGeneratingArguments)))
                ->method('generateKey')
                ->withConsecutive(...$keyGeneratingArguments)
                ->willReturnOnConsecutiveCalls(...$keyGeneratingResults);
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

        $return = call_user_func_array([$handler, $method], $arguments);

        self::assertEquals($data, $return);

        // Assert use of tags would probably need custom logic as internal property is [$tag => $tag] value and we don't want to know that.
        //$this->assertAttributeEquals([], 'tags', $cacheItem);
    }
}
