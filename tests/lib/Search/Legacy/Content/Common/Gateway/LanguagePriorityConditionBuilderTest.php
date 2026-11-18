<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Legacy\Content\Common\Gateway;

use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\LanguagePriorityConditionBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageHandlerMock;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(LanguagePriorityConditionBuilder::class)]
final class LanguagePriorityConditionBuilderTest extends TestCase
{
    public function testLoadLanguageIdsKeepsTheOrderOfTheCodes(): void
    {
        self::assertSame([8, 2, 4], $this->createBuilder()->loadLanguageIds(['ger-DE', 'eng-US', 'eng-GB']));
    }

    public function testLoadLanguageIdsThrowsOnAMissingLanguage(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('fre-FR');

        $this->createBuilder()->loadLanguageIds(['eng-US', 'fre-FR']);
    }

    /**
     * @param string[] $languages
     * @param list<int> $expectedLanguageIds
     */
    #[DataProvider('provideLanguageFilters')]
    public function testResolveMatchedLanguageIdsPicksTheFirstTranslationInTheLanguageFilter(
        array $languages,
        array $expectedLanguageIds
    ): void {
        $connection = $this->getDatabaseConnection();
        foreach ([2 => 'eng-US', 4 => 'eng-GB', 8 => 'ger-DE'] as $languageId => $locale) {
            $connection->insert('ibexa_content_language', ['id' => $languageId, 'disabled' => 0, 'locale' => $locale, 'name' => $locale]);
        }
        $rows = [];
        foreach ([1 => [8, 2], 2 => [4], 3 => [2, 4]] as $contentId => $languageIds) {
            $connection->insert('ibexa_content', ['id' => $contentId, 'initial_language_id' => $languageIds[0]]);
            foreach ($languageIds as $languageId) {
                $connection->insert('ibexa_content_translation', ['content_id' => $contentId, 'language_id' => $languageId]);
            }
            $rows[] = ['contentobject_id' => $contentId, 'matched_language_id' => $languageIds[0]];
        }
        $rows[] = end($rows);

        $rows = $this->createBuilder()->resolveMatchedLanguageIds($rows, ['languages' => $languages], 'contentobject_id');

        self::assertSame($expectedLanguageIds, array_column($rows, 'matched_language_id'));
    }

    /**
     * @return iterable<string, array{string[], list<int>}>
     */
    public static function provideLanguageFilters(): iterable
    {
        yield 'no language filter' => [[], [8, 4, 2, 2]];
        yield 'eng-GB before eng-US' => [['eng-GB', 'eng-US'], [2, 4, 4, 4]];
        yield 'eng-US before ger-DE' => [['eng-US', 'ger-DE'], [2, 4, 2, 2]];
        yield 'only ger-DE, main language for the rest' => [['ger-DE'], [8, 4, 2, 2]];
    }

    private function createBuilder(): LanguagePriorityConditionBuilder
    {
        return new LanguagePriorityConditionBuilder($this->getDatabaseConnection(), new LanguageHandlerMock());
    }
}
