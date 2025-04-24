<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\SiteAccessAware;

use Ibexa\Contracts\Core\Repository\SearchService as APIService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Core\Repository\SiteAccessAware\SearchService;
use Ibexa\Core\Repository\Values\Content\Content;

/**
 * @extends \Ibexa\Tests\Core\Repository\SiteAccessAware\AbstractServiceTestCase<
 *     \Ibexa\Contracts\Core\Repository\SearchService,
 *     \Ibexa\Core\Repository\SiteAccessAware\SearchService
 * >
 */
final class SearchServiceTest extends AbstractServiceTestCase
{
    public function getAPIServiceClassName(): string
    {
        return APIService::class;
    }

    public function getSiteAccessAwareServiceClassName(): string
    {
        return SearchService::class;
    }

    public function providerForPassTroughMethods(): array
    {
        // string $method, array $arguments, bool $return = true
        return [
            ['suggest', ['prefix', [], 11]],
            ['supports', [APIService::CAPABILITY_ADVANCED_FULLTEXT]],
        ];
    }

    public function providerForLanguagesLookupMethods(): array
    {
        $query = new Query();
        $locationQuery = new LocationQuery();
        $criterion = new Query\Criterion\ContentId(44);
        $content = new Content();
        $searchResults = new SearchResult();

        $callback = function ($languageLookup): void {
            $this->languageResolverMock
                ->expects($this->once())
                ->method('getUseAlwaysAvailable')
                ->with($languageLookup ? null : true)
                ->willReturn(true);
        };

        // string $method, array $arguments, bool $return, int $languageArgumentIndex, callable $callback
        return [
            ['findContent', [$query, self::LANG_ARG, false], $searchResults, 1, $callback],
            ['findContentInfo', [$query, self::LANG_ARG, false], $searchResults, 1, $callback],
            ['findSingle', [$criterion, self::LANG_ARG, false], $content, 1, $callback],
            ['findLocations', [$locationQuery, self::LANG_ARG, false], $searchResults, 1, $callback],
        ];
    }

    protected function setLanguagesLookupArguments(array $arguments, int $languageArgumentIndex): array
    {
        $arguments[$languageArgumentIndex] = [
            'languages' => [],
            'useAlwaysAvailable' => null,
        ];

        return $arguments;
    }

    protected function setLanguagesLookupExpectedArguments(array $arguments, int $languageArgumentIndex, array $languages): array
    {
        $arguments[$languageArgumentIndex] = [
            'languages' => $languages,
            'useAlwaysAvailable' => true,
        ];

        return $arguments;
    }

    protected function setLanguagesPassTroughArguments(array $arguments, int $languageArgumentIndex, array $languages): array
    {
        return $this->setLanguagesLookupExpectedArguments($arguments, $languageArgumentIndex, $languages);
    }
}
