<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\SiteAccessAware;

use Closure;
use Ibexa\Contracts\Core\Repository\LanguageResolver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Abstract tests for SiteAccessAware Services.
 *
 * Implies convention for methods on these services to either:
 * - Do nothing, pass-through call and optionally (default:true) return value
 * - lookup languages [IF not defined by callee] on one of the arguments given and pass it to next one.
 */
abstract class AbstractServiceTestCase extends TestCase
{
    /**
     * Purely to attempt to make tests easier to read.
     *
     * As language parameter is ignored from providers and replced with values in tests, this is used to mark value of
     * language argument instead of either askingproviders to use 0, or a valid language array which would then not be
     * used.
     */
    public const LANG_ARG = 0;

    /** @var \object|\PHPUnit\Framework\MockObject\MockObject */
    protected $innerApiServiceMock;

    /** @var object */
    protected $service;

    /** @var \Ibexa\Contracts\Core\Repository\LanguageResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $languageResolverMock;

    abstract public function getAPIServiceClassName();

    abstract public function getSiteAccessAwareServiceClassName();

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
        unset($this->service);
        unset($this->languageResolverMock);
        unset($this->innerApiServiceMock);
        parent::tearDown();
    }

    /**
     * @return array See signature on {@link testForPassTrough} for arguments and their type.
     */
    abstract public function providerForPassTroughMethods();

    /**
     * Make sure these methods does nothing more then passing the arguments to inner service.
     *
     * Methods tested here are basically those without as languages argument.
     *
     * @dataProvider providerForPassTroughMethods
     *
     * @param string $method
     * @param array $arguments
     * @param mixed $return
     */
    final public function testForPassTrough($method, array $arguments, $return = true)
    {
        if ($return) {
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

        if ($return) {
            self::assertEquals($return, $actualReturn);
        }
    }

    /**
     * @return array See signature on {@link testForLanguagesLookup} for arguments and their type.
     *               NOTE: languages / prioritizedLanguage, can be set to 0, it will be replaced by tests methods.
     */
    abstract public function providerForLanguagesLookupMethods();

    /**
     * Method to be able to customize the logic for setting expected language argument during {@see testForLanguagesLookup()}.
     *
     * @param array $arguments
     * @param int $languageArgumentIndex
     * @param array $languages
     *
     * @return array
     */
    protected function setLanguagesLookupExpectedArguments(array $arguments, $languageArgumentIndex, array $languages)
    {
        $arguments[$languageArgumentIndex] = $languages;

        return $arguments;
    }

    /**
     * Method to be able to customize the logic for setting expected language argument during {@see testForLanguagesLookup()}.
     *
     * @param array $arguments
     * @param int $languageArgumentIndex
     *
     * @return array
     */
    protected function setLanguagesLookupArguments(array $arguments, $languageArgumentIndex)
    {
        $arguments[$languageArgumentIndex] = [];

        return $arguments;
    }

    /**
     * Test that language aware methods does a language lookup when language is not set.
     *
     * @dataProvider providerForLanguagesLookupMethods
     *
     * @param string $method
     * @param array $arguments
     * @param mixed|null $return
     * @param int $languageArgumentIndex From 0 and up, so the array index on $arguments.
     */
    final public function testForLanguagesLookup($method, array $arguments, $return, $languageArgumentIndex, callable $callback = null, int $alwaysAvailableArgumentIndex = null)
    {
        $languages = ['eng-GB', 'eng-US'];

        $arguments = $this->setLanguagesLookupArguments($arguments, $languageArgumentIndex);

        $expectedArguments = $this->setLanguagesLookupExpectedArguments($arguments, $languageArgumentIndex, $languages);

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
     * @param array $arguments
     * @param int $languageArgumentIndex
     * @param array $languages
     *
     * @return array
     */
    protected function setLanguagesPassTroughArguments(array $arguments, $languageArgumentIndex, array $languages)
    {
        $arguments[$languageArgumentIndex] = $languages;

        return $arguments;
    }

    /**
     * Make sure these methods does nothing more then passing the arguments to inner service.
     *
     * @dataProvider providerForLanguagesLookupMethods
     *
     * @param string $method
     * @param array $arguments
     * @param mixed|null $return
     * @param int $languageArgumentIndex From 0 and up, so the array index on $arguments.
     */
    final public function testForLanguagesPassTrough($method, array $arguments, $return, $languageArgumentIndex, callable $callback = null, int $alwaysAvailableArgumentIndex = null)
    {
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

        if ($return) {
            self::assertEquals($return, $actualReturn);
        }
    }

    /**
     * @todo replace with coverage testing (see EZP-31035)
     */
    final public function testIfThereIsMissingTest(): void
    {
        $tested = array_merge(
            array_column($this->providerForLanguagesLookupMethods(), 0),
            array_column($this->providerForPassTroughMethods(), 0)
        );

        $class = new ReflectionClass($this->getSiteAccessAwareServiceClassName());
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor() && !in_array($method->getShortName(), $tested)) {
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
