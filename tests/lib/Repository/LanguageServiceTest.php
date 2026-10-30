<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository;

use Ibexa\Contracts\Core\Persistence\Content\Language\Handler;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Repository\LanguageService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\LanguageService
 */
final class LanguageServiceTest extends TestCase
{
    public function testGetDefaultLanguageCodeReturnsFirstConfiguredLanguage(): void
    {
        $configResolver = $this->createStub(ConfigResolverInterface::class);
        $configResolver->method('hasParameter')->willReturn(true);
        $configResolver->method('getParameter')->willReturn(['pol-PL', 'eng-GB']);

        $languageService = $this->createLanguageService(['languages' => ['ger-DE']], $configResolver);

        self::assertSame('pol-PL', $languageService->getDefaultLanguageCode());
        self::assertSame(['pol-PL', 'eng-GB'], $languageService->getPrioritizedLanguageCodeList());
    }

    public function testGetDefaultLanguageCodeReadsLanguagesSettingOnEachCall(): void
    {
        // The Repository can be built before the SiteAccess is matched, when "languages" is still empty
        $configResolver = $this->createStub(ConfigResolverInterface::class);
        $configResolver->method('hasParameter')->willReturn(true);
        $configResolver->method('getParameter')->willReturnOnConsecutiveCalls([], ['pol-PL']);

        $languageService = $this->createLanguageService([], $configResolver);

        self::assertSame('eng-GB', $languageService->getDefaultLanguageCode());
        self::assertSame('pol-PL', $languageService->getDefaultLanguageCode());
    }

    public function testGetDefaultLanguageCodeDoesNotReadUndefinedLanguagesSetting(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);
        $configResolver->method('hasParameter')->with('languages')->willReturn(false);
        $configResolver->expects(self::never())->method('getParameter');

        $languageService = $this->createLanguageService(['languages' => ['ger-DE']], $configResolver);

        self::assertSame('ger-DE', $languageService->getDefaultLanguageCode());
    }

    /**
     * @dataProvider provideForGetDefaultLanguageCodeFallsBackToSettings
     *
     * @param array<string, mixed> $settings
     * @param string[]|null $configuredLanguageCodes null when no ConfigResolver is given
     */
    public function testGetDefaultLanguageCodeFallsBackToSettings(
        array $settings,
        ?array $configuredLanguageCodes,
        string $expectedLanguageCode
    ): void {
        $configResolver = null;
        if ($configuredLanguageCodes !== null) {
            $configResolver = $this->createStub(ConfigResolverInterface::class);
            $configResolver->method('hasParameter')->willReturn(true);
            $configResolver->method('getParameter')->willReturn($configuredLanguageCodes);
        }

        $languageService = $this->createLanguageService($settings, $configResolver);

        self::assertSame($expectedLanguageCode, $languageService->getDefaultLanguageCode());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string[]|null, string}>
     */
    public static function provideForGetDefaultLanguageCodeFallsBackToSettings(): iterable
    {
        yield 'no config resolver' => [['languages' => ['ger-DE']], null, 'ger-DE'];
        yield 'empty languages setting' => [['languages' => ['ger-DE']], [], 'ger-DE'];
        yield 'empty languages setting and no languages in settings' => [[], [], 'eng-GB'];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createLanguageService(
        array $settings,
        ?ConfigResolverInterface $configResolver
    ): LanguageService {
        return new LanguageService(
            $this->createStub(Repository::class),
            $this->createStub(Handler::class),
            $this->createStub(PermissionResolver::class),
            $settings,
            $configResolver
        );
    }
}
