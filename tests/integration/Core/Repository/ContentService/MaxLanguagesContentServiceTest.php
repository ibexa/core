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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCreateContent(): void
    {
        $names = array_merge(...array_map(
            static fn (array $languageData): array => [
                $languageData['languageCode'] => $languageData['name'] . ' name',
            ],
            self::$languagesRawList
        ));
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
    }
}
