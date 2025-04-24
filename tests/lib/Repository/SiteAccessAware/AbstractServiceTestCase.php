<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\SiteAccessAware;

use Closure;
use Ibexa\Contracts\Core\Repository\LanguageResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Abstract tests for SiteAccessAware Services.
 *
 * Implies convention for methods on these services to either:
 * - Do nothing, pass through call and optionally (default:true) return value
 * - lookup languages [IF not defined by callee] on one of the arguments given and pass it to next one.
 *
 * @template TServiceInterface of object
 * @template TServiceClass of TServiceInterface
 */
abstract class AbstractServiceTestCase extends TestCase
{
    /** @phpstan-var TServiceInterface & \PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $innerApiServiceMock;

    /** @phpstan-var TServiceClass */
    protected object $service;

    protected LanguageResolver & MockObject $languageResolverMock;

    /**
     * Purely to attempt to make tests easier to read.
     *
     * As a language parameter is ignored from providers and replaced with values in tests, this is used to mark the value of a
     * language argument instead of either asking providers to use 0, or a valid language array which would then not be
     * used.
     */
    public const int LANG_ARG = 0;

    /**
     * @phpstan-return interface-string<TServiceInterface>
     */
    abstract public function getAPIServiceClassName(): string;

    /**
     * @phpstan-return class-string<TServiceClass>
     */
    abstract public function getSiteAccessAwareServiceClassName(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerApiServiceMock = $this->getMockBuilder($this->getAPIServiceClassName())->getMock();
        $this->languageResolverMock = $this->getMockBuilder(LanguageResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serviceClassName = $this->getSiteAccessAwareServiceClassName();

        $this->service = new $serviceClassName(
            $this->innerApiServiceMock,
            $this->languageResolverMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->service, $this->languageResolverMock, $this->innerApiServiceMock);
        parent::tearDown();
    }

    /**
     * See signature on {@link testForPassTrough} for arguments and their type.
     *
     * @return array<array{
     *     0: string,
     *     1: array<mixed>,
     *     2?: mixed
     * }>
     */
    abstract public function providerForPassTroughMethods(): array;

    /**
     * Make sure these methods do nothing more than passing the arguments to inner service.
     *
     * Methods tested here are basically those without the `$languages` argument.
     *
     * @dataProvider providerForPassTroughMethods
     *
     * @phpstan-param list<mixed> $arguments
     */
    final public function testForPassTrough(string $method, array $arguments, mixed $return = null): void
    {
        if (null !== $return) {
            $this->innerApiServiceMock
                ->expects(self::once())
                ->method($method)
                ->with(...$arguments)
                ->willReturn($return);
        } else {
            $this->innerApiServiceMock
                ->expects(self::once())
                ->method($method)
                ->with(...$arguments);
        }

        $actualReturn = $this->service->$method(...$arguments);

        if (null !== $return) {
            self::assertEquals($return, $actualReturn);
        }
    }

    /**
     * See signature on {@link testForLanguagesLookup} for arguments and their type.
     * NOTE: `$languages` / `$prioritizedLanguage`, can be set to 0, it will be replaced by tests methods.
     *
     * @return array<array{
     *     string,
     *     array<mixed>,
     *     mixed,
     *     int
     * }>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    abstract public function providerForLanguagesLookupMethods(): array;

    /**
     * Method to be able to customize the logic for setting expected language argument during {@see testForLanguagesLookup()}.
     *
     * @phpstan-param list<mixed> $arguments
     *
     * @param string[] $languages
     *
     * @return array<int, mixed>
     */
    protected function setLanguagesLookupExpectedArguments(
        array $arguments,
        int $languageArgumentIndex,
        array $languages
    ): array {
        $arguments[$languageArgumentIndex] = $languages;

        return $arguments;
    }

    /**
     * Method to be able to customize the logic for setting expected language argument during {@see testForLanguagesLookup()}.
     *
     * @phpstan-param list<mixed> $arguments
     *
     * @return array<int, mixed>
     */
    protected function setLanguagesLookupArguments(array $arguments, int $languageArgumentIndex): array
    {
        $arguments[$languageArgumentIndex] = [];

        return $arguments;
    }

    /**
     * Test that language-aware methods do a language lookup when the language is not set.
     *
     * @dataProvider providerForLanguagesLookupMethods
     *
     * @phpstan-param list<mixed> $arguments
     *
     * @param int $languageArgumentIndex From 0 and up, the array index on $arguments.
     */
    final public function testForLanguagesLookup(
        string $method,
        array $arguments,
        mixed $return,
        int $languageArgumentIndex,
        callable $callback = null,
        int $alwaysAvailableArgumentIndex = null
    ): void {
        $languages = ['eng-GB', 'eng-US'];

        $arguments = $this->setLanguagesLookupArguments($arguments, $languageArgumentIndex);

        $expectedArguments = $this->setLanguagesLookupExpectedArguments(
            array_values($arguments),
            $languageArgumentIndex,
            $languages
        );

        $this->languageResolverMock
            ->expects(self::once())
            ->method('getPrioritizedLanguages')
            ->with([])
            ->willReturn($languages);

        if ($alwaysAvailableArgumentIndex) {
            $arguments[$alwaysAvailableArgumentIndex] = null;
            $expectedArguments[$alwaysAvailableArgumentIndex] = true;
            $this->languageResolverMock
                ->expects(self::once())
                ->method('getUseAlwaysAvailable')
                ->with(null)
                ->willReturn(true);
        }

        $this->innerApiServiceMock
            ->expects(self::once())
            ->method($method)
            ->with(...$expectedArguments)
            ->willReturn($return);

        if ($callback instanceof Closure) {
            $callback->bindTo($this, static::class)(true);
        }

        $actualReturn = $this->service->$method(...$arguments);

        if ($return) {
            self::assertEquals($return, $actualReturn);
        }
    }

    /**
     * Method to be able to customize the logic for setting expected language argument during {@see testForLanguagesPassTrough()}.
     *
     * @phpstan-param list<mixed> $arguments
     *
     * @param string[] $languages
     *
     * @return array<int, mixed>
     */
    protected function setLanguagesPassTroughArguments(
        array $arguments,
        int $languageArgumentIndex,
        array $languages
    ): array {
        return $this->setLanguagesLookupExpectedArguments($arguments, $languageArgumentIndex, $languages);
    }

    /**
     * Make sure these methods do nothing more than passing the arguments to inner service.
     *
     * @dataProvider providerForLanguagesLookupMethods
     *
     * @phpstan-param list<mixed> $arguments
     *
     * @param int $languageArgumentIndex From 0 and up, the array index on $arguments.
     */
    final public function testForLanguagesPassTrough(
        string $method,
        array $arguments,
        mixed $return,
        int $languageArgumentIndex,
        callable $callback = null,
        int $alwaysAvailableArgumentIndex = null
    ): void {
        $languages = ['eng-GB', 'eng-US'];
        $arguments = $this->setLanguagesPassTroughArguments($arguments, $languageArgumentIndex, $languages);

        $this->languageResolverMock
            ->expects(self::once())
            ->method('getPrioritizedLanguages')
            ->with($languages)
            ->willReturn($languages);

        if ($alwaysAvailableArgumentIndex) {
            $this->languageResolverMock
                ->expects(self::once())
                ->method('getUseAlwaysAvailable')
                ->with($arguments[$alwaysAvailableArgumentIndex])
                ->willReturn($arguments[$alwaysAvailableArgumentIndex]);
        }

        $this->innerApiServiceMock
            ->expects(self::once())
            ->method($method)
            ->with(...$arguments)
            ->willReturn($return);

        if ($callback instanceof Closure) {
            $callback->bindTo($this, static::class)(false);
        }

        $actualReturn = $this->service->$method(...$arguments);

        if (null !== $return) {
            self::assertEquals($return, $actualReturn);
        }
    }

    /**
     * @todo replace with coverage testing (see EZP-31035)
     *
     * @throws \ReflectionException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    final public function testIfThereIsMissingTest(): void
    {
        $tested = array_merge(
            array_column($this->providerForLanguagesLookupMethods(), 0),
            array_column($this->providerForPassTroughMethods(), 0)
        );

        $class = new ReflectionClass($this->getSiteAccessAwareServiceClassName());
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor() && !in_array($method->getShortName(), $tested, true)) {
                $this->addWarning(
                    sprintf(
                        'Test for the %s::%s method is missing',
                        $this->getSiteAccessAwareServiceClassName(),
                        $method->getName()
                    )
                );
            }
        }
    }
}
