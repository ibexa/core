<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\SiteAccessAware;

use Ibexa\Contracts\Core\Repository\URLAliasService as APIService;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Core\Repository\SiteAccessAware\URLAliasService;
use Ibexa\Core\Repository\Values\Content\Location;

/**
 * @extends \Ibexa\Tests\Core\Repository\SiteAccessAware\AbstractServiceTest<
 *     \Ibexa\Contracts\Core\Repository\URLAliasService,
 *     \Ibexa\Core\Repository\SiteAccessAware\URLAliasService
 * >
 */
final class UrlAliasServiceTest extends AbstractServiceTest
{
    public function getAPIServiceClassName(): string
    {
        return APIService::class;
    }

    public function getSiteAccessAwareServiceClassName(): string
    {
        return URLAliasService::class;
    }

    public function providerForPassTroughMethods(): array
    {
        $location = new Location();
        $urlAlias = new URLAlias();

        // string $method, array $arguments, bool $return = true
        return [
            ['createUrlAlias', [$location, '/Tomb/Raider', 'eng-AU', true, true], $urlAlias],
            ['createGlobalUrlAlias', ['root:bla', '/Tomb/Raider', 'eng-AU', true, true], $urlAlias],
            ['listGlobalAliases', ['eng-AU', 50, 50], [$urlAlias]],
            ['removeAliases', [[555]], null],
            ['lookup', ['/James', 'eng-GB'], $urlAlias],
            ['load', ['555'], $urlAlias],
            ['refreshSystemUrlAliasesForLocation', [$location], null],
            ['deleteCorruptedUrlAliases', [], 50],
        ];
    }

    public function providerForLanguagesLookupMethods(): array
    {
        $location = new Location();
        $urlAlias = new URLAlias();

        $callback = function ($languageLookup): void {
            $this->languageResolverMock
                ->expects($this->once())
                ->method('getShowAllTranslations')
                ->with($languageLookup ? null : true)
                ->willReturn(true);
        };

        // string $method, array $arguments, bool $return, int $languageArgumentIndex, callable $callback
        return [
            ['listLocationAliases', [$location, false, 'eng-AU', null, self::LANG_ARG], [$urlAlias], 4, $callback],
            ['reverseLookup', [$location, 'eng-AU', null, self::LANG_ARG], $urlAlias, 3, $callback],
        ];
    }

    protected function setLanguagesLookupExpectedArguments(array $arguments, $languageArgumentIndex, array $languages): array
    {
        $arguments[$languageArgumentIndex] = $languages;
        $arguments[$languageArgumentIndex - 1] = true;

        return $arguments;
    }

    protected function setLanguagesPassTroughArguments(array $arguments, $languageArgumentIndex, array $languages)
    {
        return $this->setLanguagesLookupExpectedArguments($arguments, $languageArgumentIndex, $languages);
    }
}
