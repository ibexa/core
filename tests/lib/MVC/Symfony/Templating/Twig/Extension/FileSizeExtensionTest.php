<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\FileSizeExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Test\IntegrationTestCase;

/**
 * Class FileSizeExtensionTest.
 */
class FileSizeExtensionTest extends IntegrationTestCase
{
    /**
     * @param string $locale
     */
    protected $locale;

    /**
     * @param array $suffixes
     */
    protected $suffixes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB'];

    /**
     * @param TranslatorInterface|MockObject
     */
    protected $translatorMock;

    /**
     * @param ConfigResolverInterface|MockObject
     */
    protected $configResolverInterfaceMock;

    /**
     * @param LocaleConverterInterface|MockObject
     */
    protected $localeConverterInterfaceMock;

    /**
     * @param string $locale
     * @param string $defaultLocale
     */
    protected function setConfigurationLocale(
        $locale,
        $defaultLocale
    ) {
        locale_set_default($defaultLocale);
        $this->locale = $locale;
    }

    /**
     * @return string $locale
     */
    public function getLocale()
    {
        return [$this->locale];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new FileSizeExtension($this->getTranslatorInterfaceMock(), $this->suffixes, $this->getConfigResolverInterfaceMock(), $this->getLocaleConverterInterfaceMock()),
        ];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/_fixtures/functions/ibexa_file_size';
    }

    /**
     * @return ConfigResolverInterface|MockObject
     */
    protected function getConfigResolverInterfaceMock()
    {
        $configResolverInterfaceMock = $this->createMock(ConfigResolverInterface::class);
        $configResolverInterfaceMock->expects(self::any())
            ->method('getParameter')
            ->with('languages')
            ->will(self::returnValue($this->getLocale()));

        return $configResolverInterfaceMock;
    }

    /**
     * @return LocaleConverterInterface|MockObject
     */
    protected function getLocaleConverterInterfaceMock()
    {
        $this->localeConverterInterfaceMock = $this->createMock(LocaleConverterInterface::class);
        $this->localeConverterInterfaceMock->expects(self::any())
        ->method('convertToPOSIX')
        ->will(
            self::returnValueMap(
                [
                    ['fre-FR', 'fr-FR'],
                    ['eng-GB', 'en-GB'],
                ]
            )
        );

        return $this->localeConverterInterfaceMock;
    }

    /**
     * @return TranslatorInterface|MockObject
     */
    protected function getTranslatorInterfaceMock()
    {
        $that = $this;
        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->translatorMock
            ->expects(self::any())->method('trans')->will(
                self::returnCallback(
                    static function ($suffixes) use ($that) {
                        foreach ($that->getLocale() as $value) {
                            if ($value === 'fre-FR') {
                                return $suffixes . ' French version';
                            } elseif ($value === 'eng-GB') {
                                return $suffixes . ' English version';
                            } else {
                                return $suffixes . ' wrong local so we take the default one which is en-GB here';
                            }
                        }
                    }
                )
            );

        return $this->translatorMock;
    }
}
