<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

/**
 * Test case proving the language bitmask's old ~62-language ceiling (8 * PHP_INT_SIZE - 2, from
 * language ids being powers of two) is gone now that language ids are plain sequential integers.
 *
 * @see \Ibexa\Contracts\Core\Repository\LanguageService
 *
 * @group integration
 * @group language
 */
class LanguageServiceMaximumSupportedLanguagesTest extends BaseTestCase
{
    /** @var \Ibexa\Contracts\Core\Repository\LanguageService */
    private $languageService;

    /** @var array */
    private $createdLanguages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageService = $this->getRepository()->getContentLanguageService();
    }

    protected function tearDown(): void
    {
        while (($language = array_pop($this->createdLanguages)) !== null) {
            $this->languageService->deleteLanguage($language);
        }

        parent::tearDown();
    }

    /**
     * Creates more languages than the old bitmask ceiling (8 * PHP_INT_SIZE - 2, i.e. 62 on 64-bit
     * PHP) ever allowed, proving the limit no longer exists.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LanguageService::createLanguage()
     */
    public function testCreateMoreLanguagesThanOldBitmaskLimit(): void
    {
        $existingLanguageCount = count($this->languageService->loadLanguages());
        $countToCreate = (8 * PHP_INT_SIZE - 2) - $existingLanguageCount + 10;

        $languageCreate = $this->languageService->newLanguageCreateStruct();
        $languageCreate->enabled = true;

        for ($i = 1; $i <= $countToCreate; ++$i) {
            $languageCreate->name = "Language $i";
            $languageCreate->languageCode = sprintf('lan-%03d', $i);

            $this->createdLanguages[] = $this->languageService->createLanguage($languageCreate);
        }

        self::assertCount(
            $existingLanguageCount + $countToCreate,
            $this->languageService->loadLanguages()
        );
    }
}
