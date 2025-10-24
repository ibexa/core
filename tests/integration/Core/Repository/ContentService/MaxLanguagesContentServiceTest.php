<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\Exceptions\Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
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
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareMaxLanguages();
    }

    /**
     * @throws Exception
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
        $this->createFolder($names);
    }

    /**
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
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
    }
}
