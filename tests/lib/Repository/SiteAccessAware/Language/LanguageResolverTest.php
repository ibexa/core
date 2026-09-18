<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\SiteAccessAware\Language;

use Ibexa\Core\Repository\SiteAccessAware\Language\AbstractLanguageResolver;
use Ibexa\Core\Repository\SiteAccessAware\Language\LanguageResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractLanguageResolver::class)]
#[CoversClass(LanguageResolver::class)]
class LanguageResolverTest extends TestCase
{
    /**
     * @param array $expectedPrioritizedLanguagesList
     * @param array $configLanguages
     * @param bool $defaultShowAllTranslations
     * @param array|null $forcedLanguages
     * @param string|null $contextLanguage
     */
    #[DataProvider('getDataForTestGetPrioritizedLanguages')]
    public function testGetPrioritizedLanguages(
        array $expectedPrioritizedLanguagesList,
        array $configLanguages,
        bool $defaultShowAllTranslations,
        ?array $forcedLanguages,
        ?string $contextLanguage
    ): void {
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

    /**
     * Data provider for testGetPrioritizedLanguages.
     *
     * @see testGetPrioritizedLanguages
     *
     * @return array
     */
    public static function getDataForTestGetPrioritizedLanguages(): array
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
