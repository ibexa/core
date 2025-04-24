<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

/**
 * Abstract test case for spi cache impl.
 *
 * @phpstan-import-type TAdditionalCalls from \Ibexa\Tests\Core\Persistence\Cache\AbstractBaseHandlerTestCase
 */
abstract class AbstractCacheHandlerTestCase extends AbstractBaseHandlerTestCase
{
    abstract public function getHandlerMethodName(): string;

    /**
     * @phpstan-return class-string
     */
    abstract public function getHandlerClassName(): string;

    /**
     * @phpstan-return iterable<
     *      string,
     *      array{
     *          0: string,
     *          1: list<mixed>,
     *          2?: null|list<mixed>,
     *          3?: null|list<mixed>,
     *          4?: null|list<string>,
     *          5?: null|string|list<string>,
     *          6?: mixed
     *      }
     * >
     */
    abstract public function providerForUnCachedMethods(): iterable;

    /**
     * @dataProvider providerForUnCachedMethods
     *
     * @phpstan-param list<mixed> $arguments
     * @phpstan-param list<mixed>|null $tagGeneratingArguments
     * @phpstan-param list<mixed>|null $keyGeneratingArguments
     * @phpstan-param list<string>|null $tags
     * @phpstan-param string|list<string>|null $key
     */
    final public function testUnCachedMethods(
        string $method,
        array $arguments,
        array $tagGeneratingArguments = null,
        array $keyGeneratingArguments = null,
        array $tags = null,
        string|array|null $key = null,
        mixed $returnValue = null
    ): void {
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
            if (null !== $tagGeneratingArguments) {
                $this->cacheIdentifierGeneratorMock
                    ->expects(self::exactly(count($tagGeneratingArguments)))
                    ->method('generateTag')
                    ->withConsecutive(...$tagGeneratingArguments)
                    ->willReturnOnConsecutiveCalls(...($tags ?? []));
            }

            if (null !== $keyGeneratingArguments) {
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
        $actualReturnValue = $handler->$method(...$arguments);

        self::assertEquals($returnValue, $actualReturnValue);
    }

    /**
     * @phpstan-return list<array{
     *     0: string,
     *     1: list<mixed>,
     *     2: string,
     *     3?: list<mixed>|null,
     *     4?: list<mixed>|null,
     *     5?: list<mixed>|null,
     *     6?: list<mixed>|null,
     *     7?: mixed,
     *     8?: bool,
     *     9?: TAdditionalCalls
     * }>
     */
    abstract public function providerForCachedLoadMethodsHit(): array;

    /**
     * @dataProvider providerForCachedLoadMethodsHit
     *
     * @phpstan-param list<mixed> $arguments
     * @phpstan-param list<mixed>|null $tagGeneratingArguments
     * @phpstan-param list<mixed>|null $tagGeneratingResults
     * @phpstan-param list<mixed>|null $keyGeneratingArguments
     * @phpstan-param list<mixed>|null $keyGeneratingResults
     * @phpstan-param bool $multi Default false, set to true if the method will look up several cache items.
     * @phpstan-param TAdditionalCalls $additionalCalls Sets of additional calls being made to handlers, with 4 values (0: handler name, 1: handler class, 2: method, 3: return data)
     */
    final public function testLoadMethodsCacheHit(
        string $method,
        array $arguments,
        string $key,
        array $tagGeneratingArguments = null,
        array $tagGeneratingResults = null,
        array $keyGeneratingArguments = null,
        array $keyGeneratingResults = null,
        mixed $data = null,
        bool $multi = false,
        array $additionalCalls = []
    ): void {
        $cacheItem = $this->getCacheItem($key, $multi ? reset($data) : $data);
        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::never())->method('logCall');

        if ($tagGeneratingArguments) {
            $this->cacheIdentifierGeneratorMock
                ->expects(self::exactly(count($tagGeneratingArguments)))
                ->method('generateTag')
                ->withConsecutive(...$tagGeneratingArguments)
                ->willReturnOnConsecutiveCalls(...($tagGeneratingResults ?? []));
        }

        if ($keyGeneratingArguments) {
            $this->cacheIdentifierGeneratorMock
                ->expects(self::exactly(count($keyGeneratingArguments)))
                ->method('generateKey')
                ->withConsecutive(...$keyGeneratingArguments)
                ->willReturnOnConsecutiveCalls(...($keyGeneratingResults ?? []));
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
        $return = $handler->$method(...$arguments);

        self::assertEquals($data, $return);
    }

    /**
     * @phpstan-return list<array{
     *     0: string,
     *     1: list<mixed>,
     *     2: string,
     *     3?: list<mixed>|null,
     *     4?: list<mixed>|null,
     *     5?: list<mixed>|null,
     *     6?: list<mixed>|null,
     *     7?: mixed,
     *     8?: bool,
     *     9?: TAdditionalCalls
     * }>
     */
    abstract public function providerForCachedLoadMethodsMiss(): array;

    /**
     * @dataProvider providerForCachedLoadMethodsMiss
     *
     * @param bool $multi Default false, set to true if the method will look up several cache items.
     *
     * @phpstan-param list<mixed> $arguments
     * @phpstan-param list<mixed>|null $tagGeneratingArguments
     * @phpstan-param list<mixed>|null $tagGeneratingResults
     * @phpstan-param list<mixed>|null $keyGeneratingArguments
     * @phpstan-param list<mixed>|null $keyGeneratingResults
     * @phpstan-param TAdditionalCalls $additionalCalls Sets of additional calls being made to handlers, with 4 values (0: handler name, 1: handler class, 2: method, 3: return data)
     */
    final public function testLoadMethodsCacheMiss(
        string $method,
        array $arguments,
        string $key,
        array $tagGeneratingArguments = null,
        array $tagGeneratingResults = null,
        array $keyGeneratingArguments = null,
        array $keyGeneratingResults = null,
        mixed $data = null,
        bool $multi = false,
        array $additionalCalls = []
    ): void {
        $cacheItem = $this->getCacheItem($key);
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
                ->willReturnOnConsecutiveCalls(...($tagGeneratingResults ?? []));
        }

        if ($keyGeneratingArguments) {
            $this->cacheIdentifierGeneratorMock
                ->expects(self::exactly(count($keyGeneratingArguments)))
                ->method('generateKey')
                ->withConsecutive(...$keyGeneratingArguments)
                ->willReturnOnConsecutiveCalls(...($keyGeneratingResults ?? []));
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

        $return = $handler->$method(...$arguments);

        self::assertEquals($data, $return);

        // Assert use of tags would probably need custom logic as internal property is [$tag => $tag] value and we don't want to know that.
        //$this->assertAttributeEquals([], 'tags', $cacheItem);
    }
}
