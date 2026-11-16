<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\SiteAccessAware\Language;

use Ibexa\Contracts\Core\Repository\Exceptions\OutOfBoundsException;
use Ibexa\Core\Repository\SiteAccessAware\Language\LanguageResolver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\SiteAccessAware\Language\AbstractLanguageResolver
 * @covers \Ibexa\Core\Repository\SiteAccessAware\Language\LanguageResolver
 */
class LanguageResolverTest extends TestCase
{
    /**
     * @dataProvider getDataForTestGetPrioritizedLanguages
     *
     * @param array $expectedPrioritizedLanguagesList
     * @param array $configLanguages
     * @param bool $defaultShowAllTranslations
     * @param array|null $forcedLanguages
     * @param string|null $contextLanguage
     */
    public function testGetPrioritizedLanguages(
        array $expectedPrioritizedLanguagesList,
        array $configLanguages,
        bool $defaultShowAllTranslations,
        ?array $forcedLanguages,
        ?string $contextLanguage
    ) {
        // note: "use always available" does not affect this test
        $defaultUseAlwaysAvailable = true;

        $languageResolver = new LanguageResolver(
            $configLanguages,
            $defaultUseAlwaysAvailable,
            $defaultShowAllTranslations
        );

        $languageResolver->setContextLanguage($contextLanguage);

        self::assertEquals(
            $expectedPrioritizedLanguagesList,
            $languageResolver->getPrioritizedLanguages($forcedLanguages)
        );
    }

    public function testGetFirstPrioritizedLanguage(): void
    {
        $languageResolver = new LanguageResolver(
            configLanguages: ['pol-PL', 'eng-GB'],
            defaultUseAlwaysAvailable: true,
            defaultShowAllTranslations: false
        );

        self::assertEquals('eng-GB', $languageResolver->getFirstPrioritizedLanguage());
    }

    public function testGetFirstPrioritizedLanguageThrowsExceptionWhenNoLanguageFound(): void
    {
        $this->expectException(OutOfBoundsException::class);

        $languageResolver = new LanguageResolver(
            configLanguages: [],
            defaultUseAlwaysAvailable: true,
            defaultShowAllTranslations: false
        );
        $languageResolver->getFirstPrioritizedLanguage();
    }

    /**
     * Data provider for testGetPrioritizedLanguages.
     *
     * @see testGetPrioritizedLanguages
     *
     * @return array
     */
    public function getDataForTestGetPrioritizedLanguages(): array
    {
        return [
            [
                ['eng-GB', 'pol-PL'], ['eng-GB', 'pol-PL'], false, null, null,
            ],
            [
                [], ['eng-GB', 'pol-PL'], false, [], null, ],
            [
                ['ger-DE'], ['eng-GB', 'pol-PL'], false, ['ger-DE'], null,
            ],
            [
                [], ['eng-GB', 'pol-PL'], true, null, null,
            ],
            [
                ['ger-DE', 'eng-GB', 'pol-PL'], ['eng-GB', 'pol-PL'], false, null, 'ger-DE',
            ],
        ];
    }
}
