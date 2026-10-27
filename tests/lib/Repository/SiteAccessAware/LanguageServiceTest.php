<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\SiteAccessAware;

use Ibexa\Contracts\Core\Repository\LanguageService as APIService;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LanguageCreateStruct;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Repository\SiteAccessAware\LanguageService;

class LanguageServiceTest extends AbstractServiceTest
{
    protected function setUp(): void
    {
        // Unlike other SiteAccess aware services, LanguageService takes a ConfigResolver instead of a LanguageResolver
        $this->innerApiServiceMock = $this->createMock(APIService::class);
        $this->service = new LanguageService($this->innerApiServiceMock);
    }

    public function getAPIServiceClassName()
    {
        return APIService::class;
    }

    public function getSiteAccessAwareServiceClassName()
    {
        return LanguageService::class;
    }

    public function providerForPassTroughMethods()
    {
        $languageCreateStruct = new LanguageCreateStruct();
        $language = new Language();

        // string $method, array $arguments, bool $return = true
        return [
            ['createLanguage', [$languageCreateStruct], $language],

            ['updateLanguageName', [$language, 'Afrikaans'], $language],

            ['enableLanguage', [$language], $language],

            ['disableLanguage', [$language], $language],

            ['loadLanguage', ['eng-GB'], $language],
            ['loadLanguageListByCode', [['eng-GB']], []],

            ['loadLanguages', [], []],

            ['loadLanguageById', [4], $language],
            ['loadLanguageListById', [[4]], []],

            ['deleteLanguage', [$language], null],

            // Passed through only when no "languages" setting is available, see testGetDefaultLanguageCode*()
            ['getDefaultLanguageCode', [], ''],

            ['newLanguageCreateStruct', [], $languageCreateStruct],
        ];
    }

    public function providerForLanguagesLookupMethods()
    {
        // string $method, array $arguments, bool $return, int $languageArgumentIndex
        return [];
    }

    public function testGetDefaultLanguageCodeReturnsFirstConfiguredLanguage(): void
    {
        $configResolver = $this->createStub(ConfigResolverInterface::class);
        $configResolver->method('hasParameter')->willReturn(true);
        $configResolver->method('getParameter')->willReturn(['pol-PL', 'eng-GB']);

        $innerService = $this->createMock(APIService::class);
        $innerService->expects(self::never())->method('getDefaultLanguageCode');

        $service = new LanguageService($innerService, $configResolver);

        self::assertSame('pol-PL', $service->getDefaultLanguageCode());
    }

    public function testGetDefaultLanguageCodeDoesNotReadUndefinedLanguagesSetting(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);
        $configResolver->method('hasParameter')->with('languages')->willReturn(false);
        $configResolver->expects(self::never())->method('getParameter');

        $service = new LanguageService($this->createInnerServiceReturningDefaultLanguage('eng-GB'), $configResolver);

        self::assertSame('eng-GB', $service->getDefaultLanguageCode());
    }

    public function testGetDefaultLanguageCodeFallsBackToInnerServiceForEmptyLanguagesSetting(): void
    {
        $configResolver = $this->createStub(ConfigResolverInterface::class);
        $configResolver->method('hasParameter')->willReturn(true);
        $configResolver->method('getParameter')->willReturn([]);

        $service = new LanguageService($this->createInnerServiceReturningDefaultLanguage('eng-GB'), $configResolver);

        self::assertSame('eng-GB', $service->getDefaultLanguageCode());
    }

    public function testGetDefaultLanguageCodeFallsBackToInnerServiceWithoutConfigResolver(): void
    {
        $service = new LanguageService($this->createInnerServiceReturningDefaultLanguage('eng-GB'));

        self::assertSame('eng-GB', $service->getDefaultLanguageCode());
    }

    private function createInnerServiceReturningDefaultLanguage(string $languageCode): APIService
    {
        $innerService = $this->createStub(APIService::class);
        $innerService->method('getDefaultLanguageCode')->willReturn($languageCode);

        return $innerService;
    }
}

class_alias(LanguageServiceTest::class, 'eZ\Publish\Core\Repository\SiteAccessAware\Tests\LanguageServiceTest');
