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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LanguageService::class)]
final class LanguageServiceTest extends TestCase
{
    public function testGetDefaultLanguageCodeReturnsFirstConfiguredLanguage(): void
    {
        $configResolver = $this->createConfigResolver(['pol-PL', 'eng-GB']);

        $languageService = $this->createLanguageService(['languages' => ['ger-DE']], $configResolver);

        self::assertSame('pol-PL', $languageService->getDefaultLanguageCode());
        self::assertSame(['pol-PL', 'eng-GB'], $languageService->getPrioritizedLanguageCodeList());
    }

    public function testGetDefaultLanguageCodeReadsLanguagesSettingOnEachCall(): void
    {
        // The Repository can be built before the SiteAccess is matched, when "languages" is still empty
        $configResolver = $this->createMock(ConfigResolverInterface::class);
        $configResolver->expects($this->exactly(2))
            ->method('hasParameter')
            ->with('languages')
            ->willReturn(true);
        $configResolver->expects($this->exactly(2))
            ->method('getParameter')
            ->with('languages')
            ->willReturnOnConsecutiveCalls([], ['pol-PL']);

        $languageService = $this->createLanguageService([], $configResolver);

        self::assertSame('eng-GB', $languageService->getDefaultLanguageCode());
        self::assertSame('pol-PL', $languageService->getDefaultLanguageCode());
    }

    public function testGetDefaultLanguageCodeDoesNotReadUndefinedLanguagesSetting(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);
        $configResolver->expects($this->once())->method('hasParameter')->with('languages')->willReturn(false);
        $configResolver->expects($this->never())->method('getParameter');

        $languageService = $this->createLanguageService(['languages' => ['ger-DE']], $configResolver);

        self::assertSame('ger-DE', $languageService->getDefaultLanguageCode());
    }

    /**
     * @param array<string, mixed> $settings
     * @param list<string>|null $configuredLanguageCodes null when no ConfigResolver is given
     */
    #[DataProvider('provideForGetDefaultLanguageCodeFallsBackToSettings')]
    public function testGetDefaultLanguageCodeFallsBackToSettings(
        array $settings,
        ?array $configuredLanguageCodes,
        string $expectedLanguageCode
    ): void {
        $configResolver = null;
        if ($configuredLanguageCodes !== null) {
            $configResolver = $this->createConfigResolver($configuredLanguageCodes);
        }

        $languageService = $this->createLanguageService($settings, $configResolver);

        self::assertSame($expectedLanguageCode, $languageService->getDefaultLanguageCode());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<string>|null, string}>
     */
    public static function provideForGetDefaultLanguageCodeFallsBackToSettings(): iterable
    {
        yield 'no config resolver' => [['languages' => ['ger-DE']], null, 'ger-DE'];
        yield 'empty languages setting' => [['languages' => ['ger-DE']], [], 'ger-DE'];
        yield 'empty languages setting and no languages in settings' => [[], [], 'eng-GB'];
    }

    /**
     * @param list<string> $languageCodes
     */
    private function createConfigResolver(array $languageCodes): ConfigResolverInterface
    {
        $configResolver = self::createStub(ConfigResolverInterface::class);
        $configResolver->method('hasParameter')->willReturnMap([['languages', null, null, true]]);
        $configResolver->method('getParameter')->willReturnMap([['languages', null, null, $languageCodes]]);

        return $configResolver;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createLanguageService(
        array $settings,
        ?ConfigResolverInterface $configResolver
    ): LanguageService {
        return new LanguageService(
            self::createStub(Repository::class),
            self::createStub(Handler::class),
            self::createStub(PermissionResolver::class),
            $settings,
            $configResolver
        );
    }
}
