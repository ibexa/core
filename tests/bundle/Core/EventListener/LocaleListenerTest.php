<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\LocaleListener;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LocaleListenerTest extends TestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localeConverter;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configResolver;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    protected function setUp(): void
    {
        $parameterBagMock = $this->createMock(ParameterBag::class);
        $parameterBagMock->expects(self::never())->method(self::anything());

        $requestMock = $this->createMock(Request::class);
        $requestMock->attributes = $parameterBagMock;

        $this->localeConverter = $this->createMock(LocaleConverterInterface::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->requestStack = new RequestStack([$requestMock]);
    }

    /**
     * @dataProvider onKernelRequestProvider
     */
    public function testOnKernelRequest(array $configuredLanguages, array $convertedLocalesValueMap, $expectedLocale): void
    {
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('languages')
            ->will(self::returnValue($configuredLanguages));

        $this->localeConverter
            ->expects(self::atLeastOnce())
            ->method('convertToPOSIX')
            ->will(
                self::returnValueMap($convertedLocalesValueMap)
            );

        $defaultLocale = 'en';
        $baseLocaleListener = new BaseLocaleListener($this->requestStack, $defaultLocale);
        $localeListener = new LocaleListener($baseLocaleListener, $this->configResolver, $this->localeConverter);

        $request = new Request();
        $localeListener->onKernelRequest(
            new RequestEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST
            )
        );
        self::assertSame($expectedLocale, $request->attributes->get('_locale'));
    }

    public function onKernelRequestProvider(): array
    {
        return [
            [
                ['eng-GB'],
                [
                    ['eng-GB', 'en_GB'],
                ],
                'en_GB',
            ],
            [
                ['eng-DE'],
                [
                    ['eng-DE', null],
                ],
                // Default locale
                null,
            ],
            [
                ['fre-CA', 'fre-FR', 'eng-US'],
                [
                    ['fre-CA', null],
                    ['fre-FR', 'fr_FR'],
                ],
                'fr_FR',
            ],
            [
                ['fre-CA', 'fre-FR', 'eng-US'],
                [
                    ['fre-CA', null],
                    ['fre-FR', null],
                    ['eng-US', null],
                ],
                null,
            ],
            [
                ['esl-ES', 'eng-GB'],
                [
                    ['esl-ES', 'es_ES'],
                    ['eng-GB', 'en_GB'],
                ],
                'es_ES',
            ],
        ];
    }
}
