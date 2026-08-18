<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService
 */
final class MaxLanguagesContentServiceTest extends RepositoryTestCase
{
    /** @var list<array{languageCode: string, name: string }> */
    private static array $languagesRawList = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$languagesRawList = Yaml::parseFile(dirname(__DIR__) . '/_fixtures/max_languages.yaml');
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareMaxLanguages();
    }

    /**
     * Number of languages to create beyond the fixture's 62 (the old bitmask ceiling,
     * 8 * PHP_INT_SIZE - 2 on 64-bit PHP) - proves content creation works with translations in
     * languages allocated past where the old power-of-two id scheme would have thrown.
     */
    private const EXTRA_LANGUAGES_BEYOND_OLD_LIMIT = 5;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCreateContent(): void
    {
        if (getenv('SEARCH_ENGINE') !== 'legacy') {
            self::markTestSkipped('Skipped on non-LSE as it requires specific configuration');
        }

        $names = array_merge(...array_map(
            static fn (array $languageData): array => [
                $languageData['languageCode'] => $languageData['name'] . ' name',
            ],
            self::$languagesRawList
        ));

        for ($i = 1; $i <= self::EXTRA_LANGUAGES_BEYOND_OLD_LIMIT; ++$i) {
            $names["xtr-{$i}"] = "Beyond old limit {$i} name";
        }

        $this->createFolder($names);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function prepareMaxLanguages(): void
    {
        $languageService = self::getLanguageService();

        foreach (self::$languagesRawList as $languageData) {
            $languageCreateStruct = $languageService->newLanguageCreateStruct();
            $languageCreateStruct->languageCode = $languageData['languageCode'];
            $languageCreateStruct->name = $languageData['name'];
            $languageService->createLanguage($languageCreateStruct);
        }

        for ($i = 1; $i <= self::EXTRA_LANGUAGES_BEYOND_OLD_LIMIT; ++$i) {
            $languageCreateStruct = $languageService->newLanguageCreateStruct();
            $languageCreateStruct->languageCode = "xtr-{$i}";
            $languageCreateStruct->name = "Beyond old limit {$i}";
            $languageService->createLanguage($languageCreateStruct);
        }
    }
}
