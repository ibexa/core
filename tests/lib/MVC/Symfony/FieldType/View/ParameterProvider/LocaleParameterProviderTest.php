<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\FieldType\View\ParameterProvider;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProvider\LocaleParameterProvider;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LocaleParameterProviderTest extends TestCase
{
    public function providerForTestGetViewParameters()
    {
        return [
            [true, 'fr_FR'],
            [false, 'hr_HR'],
        ];
    }

    /**
     * @dataProvider providerForTestGetViewParameters
     */
    public function testGetViewParameters(
        $hasRequestLocale,
        $expectedLocale
    ) {
        $field = new Field(['languageCode' => 'cro-HR']);
        $parameterProvider = new LocaleParameterProvider($this->getLocaleConverterMock());
        $parameterProvider->setRequestStack($this->getRequestStackMock($hasRequestLocale));
        self::assertSame(
            ['locale' => $expectedLocale],
            $parameterProvider->getViewParameters($field)
        );
    }

    protected function getRequestStackMock($hasLocale)
    {
        $parameterBagMock = $this->createMock(ParameterBag::class);

        $parameterBagMock->expects(self::any())
            ->method('has')
            ->with(self::equalTo('_locale'))
            ->will(self::returnValue($hasLocale));

        $parameterBagMock->expects(self::any())
            ->method('get')
            ->with(self::equalTo('_locale'))
            ->will(self::returnValue('fr_FR'));

        $requestMock = $this->createMock(Request::class);
        $requestMock->attributes = $parameterBagMock;

        return new RequestStack([$requestMock]);
    }

    protected function getLocaleConverterMock()
    {
        $mock = $this->createMock(LocaleConverterInterface::class);

        $mock->expects(self::any())
            ->method('convertToPOSIX')
            ->with(self::equalTo('cro-HR'))
            ->will(self::returnValue('hr_HR'));

        return $mock;
    }
}
