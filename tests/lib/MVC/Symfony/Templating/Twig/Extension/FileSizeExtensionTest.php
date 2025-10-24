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

final class FileSizeExtensionTest extends IntegrationTestCase
{
    protected string $locale = '';

    /** @var string[] */
    protected array $suffixes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB'];

    protected TranslatorInterface & MockObject $translatorMock;

    protected ConfigResolverInterface & MockObject $configResolverInterfaceMock;

    protected LocaleConverterInterface & MockObject $localeConverterInterfaceMock;

    protected function setConfigurationLocale(
        string $locale,
        string $defaultLocale
    ): void {
        locale_set_default($defaultLocale);
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    protected function getExtensions(): array
    {
        return [
            new FileSizeExtension($this->getTranslatorInterfaceMock(), $this->suffixes, $this->getConfigResolverInterfaceMock(), $this->getLocaleConverterInterfaceMock()),
        ];
    }

    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/_fixtures/functions/ibexa_file_size';
    }

    protected function getConfigResolverInterfaceMock(): ConfigResolverInterface & MockObject
    {
        $configResolverInterfaceMock = $this->createMock(ConfigResolverInterface::class);
        $configResolverInterfaceMock->expects(self::atLeastOnce())
            ->method('getParameter')
            ->with('languages')
            ->willReturn([$this->getLocale()]);

        return $configResolverInterfaceMock;
    }

    protected function getLocaleConverterInterfaceMock(): LocaleConverterInterface & MockObject
    {
        $this->localeConverterInterfaceMock = $this->createMock(LocaleConverterInterface::class);
        $this->localeConverterInterfaceMock->expects(self::atLeastOnce())
        ->method('convertToPOSIX')
        ->willReturnMap(
            [
                ['fre-FR', 'fr-FR'],
                ['eng-GB', 'en-GB'],
            ]
        );

        return $this->localeConverterInterfaceMock;
    }

    protected function getTranslatorInterfaceMock(): TranslatorInterface & MockObject
    {
        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->translatorMock
            ->expects(self::atLeastOnce())
            ->method('trans')->willReturnCallback(
                function ($suffixes): string {
                    return match ($this->getLocale()) {
                        'fre-FR' => $suffixes . ' French version',
                        'eng-GB' => $suffixes . ' English version',
                        default => $suffixes . ' wrong locale so we take the default one which is en-GB here',
                    };
                }
            );

        return $this->translatorMock;
    }
}
