<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

/**
 * @group integration
 * @group search
 */
final class SearchServiceManyLanguagesTest extends BaseTestCase
{
    public function testFindContentWithManyLanguages(): void
    {
        $repository = $this->getRepository();
        $languageService = $repository->getContentLanguageService();
        $searchService = $repository->getSearchService();

        // Create 50 languages to trigger maxBooleanClauses (usually 1024)
        $languages = ['eng-GB'];
        for ($i = 0; $i < 50; ++$i) {
            $code = sprintf('tst-%02d', $i);
            $langCreateStruct = $languageService->newLanguageCreateStruct();
            $langCreateStruct->languageCode = $code;
            $langCreateStruct->name = "Test Language $i";
            $langCreateStruct->enabled = true;

            try {
                $languageService->createLanguage($langCreateStruct);
            } catch (Exception $e) {
                // Ignore if already exists
            }
            $languages[] = $code;
        }

        $query = new Query();
        $query->filter = new Criterion\MatchAll();
        $query->limit = 1;

        $languageSettings = [
            'languages' => $languages,
            'useAlwaysAvailable' => true,
        ];

        // This should not throw maxBooleanClauses exception
        try {
            $result = $searchService->findContent($query, $languageSettings);
        } catch (Exception $e) {
            $this->fail('Search failed with many languages: ' . $e->getMessage());
        }
        $this->assertGreaterThan(0, $result->totalCount);
    }
}
