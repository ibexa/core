<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Helper;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TranslationHelperTest extends TestCase
{
    private ConfigResolverInterface & MockObject $configResolver;

    private ContentService & MockObject $contentService;

    private LoggerInterface & MockObject $logger;

    private TranslationHelper $translationHelper;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Field[] */
    private array $translatedFields;

    /** @var string[] */
    private array $translatedNames;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->contentService = $this->createMock(ContentService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $siteAccessByLanguages = [
            'fre-FR' => ['fre'],
            'eng-GB' => ['my_siteaccess', 'eng'],
            'esl-ES' => ['esl', 'mex'],
            'heb-IL' => ['heb'],
        ];
        $this->translationHelper = new TranslationHelper(
            $this->configResolver,
            $this->contentService,
            $siteAccessByLanguages,
            $this->logger
        );
        $this->translatedNames = [
            'eng-GB' => 'My name in english',
            'fre-FR' => 'Mon nom en français',
            'esl-ES' => 'Mi nombre en español',
            'heb-IL' => 'השם שלי בעברית',
        ];
        $this->translatedFields = [
            'eng-GB' => new Field(['value' => 'Content in english', 'fieldDefIdentifier' => 'test', 'languageCode' => 'eng-GB']),
            'fre-FR' => new Field(['value' => 'Contenu en français', 'fieldDefIdentifier' => 'test', 'languageCode' => 'fre-FR']),
            'esl-ES' => new Field(['value' => 'Contenido en español', 'fieldDefIdentifier' => 'test', 'languageCode' => 'esl-ES']),
            'heb-IL' => new Field(['value' => 'תוכן בספרדית', 'fieldDefIdentifier' => 'test', 'languageCode' => 'heb-IL']),
        ];
    }

    /**
     * @return \Ibexa\Core\Repository\Values\Content\Content
     */
    private function generateContent(): Content
    {
        return new Content(
            [
                'versionInfo' => $this->generateVersionInfo(),
                'internalFields' => $this->translatedFields,
            ]
        );
    }

    private function generateVersionInfo(): APIVersionInfo
    {
        return new VersionInfo(
            [
                'names' => $this->translatedNames,
                'initialLanguageCode' => 'fre-FR',
            ]
        );
    }

    /**
     * @dataProvider getTranslatedNameProvider
     *
     * @param array<string> $prioritizedLanguages
     * @param string $expectedLocale
     */
    public function testGetTranslatedName(array $prioritizedLanguages, string $expectedLocale): void
    {
        $content = $this->generateContent();
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('languages')
            ->willReturn($prioritizedLanguages);

        self::assertSame($this->translatedNames[$expectedLocale], $this->translationHelper->getTranslatedContentName($content));
    }

    /**
     * @dataProvider getTranslatedNameProvider
     *
     * @param array<string> $prioritizedLanguages
     * @param string $expectedLocale
     */
    public function testGetTranslatedNameByContentInfo(array $prioritizedLanguages, string $expectedLocale): void
    {
        $versionInfo = $this->generateVersionInfo();
        $contentInfo = new ContentInfo(['id' => 123]);
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('languages')
            ->willReturn($prioritizedLanguages);

        $this->contentService
            ->expects(self::once())
            ->method('loadVersionInfo')
            ->with($contentInfo)
            ->willReturn($versionInfo);

        self::assertSame($this->translatedNames[$expectedLocale], $this->translationHelper->getTranslatedContentNameByContentInfo($contentInfo));
    }

    /**
     * @phpstan-return list<array{array<string>, string}>
     */
    public function getTranslatedNameProvider(): array
    {
        return [
            [['fre-FR', 'eng-GB'], 'fre-FR'],
            [['esl-ES', 'fre-FR'], 'esl-ES'],
            [['eng-US', 'heb-IL'], 'heb-IL'],
            [['eng-US', 'eng-GB'], 'eng-GB'],
            [['eng-US', 'ger-DE'], 'fre-FR'],
        ];
    }

    public function testGetTranslatedNameByContentInfoForcedLanguage(): void
    {
        $versionInfo = $this->generateVersionInfo();
        $contentInfo = new ContentInfo(['id' => 123]);
        $this->configResolver
            ->expects(self::never())
            ->method('getParameter');

        $this->contentService
            ->expects(self::exactly(2))
            ->method('loadVersionInfo')
            ->with($contentInfo)
            ->willReturn($versionInfo);

        self::assertSame('My name in english', $this->translationHelper->getTranslatedContentNameByContentInfo($contentInfo, 'eng-GB'));
        self::assertSame('Mon nom en français', $this->translationHelper->getTranslatedContentNameByContentInfo($contentInfo, 'eng-US'));
    }

    public function testGetTranslatedNameByContentInfoForcedLanguageMainLanguage(): void
    {
        $name = 'Name in main language';
        $mainLanguage = 'eng-GB';
        $contentInfo = new ContentInfo(
            [
                'id' => 123,
                'mainLanguageCode' => $mainLanguage,
                'name' => $name,
            ]
        );
        $this->configResolver
            ->expects(self::never())
            ->method('getParameter');

        $this->contentService
            ->expects(self::never())
            ->method('loadContentByContentInfo');

        self::assertSame(
            $name,
            $this->translationHelper->getTranslatedContentNameByContentInfo($contentInfo, $mainLanguage)
        );
    }

    public function testGetTranslatedNameForcedLanguage(): void
    {
        $content = $this->generateContent();
        $this->configResolver
            ->expects(self::never())
            ->method('getParameter');

        self::assertSame('My name in english', $this->translationHelper->getTranslatedContentName($content, 'eng-GB'));
        self::assertSame('Mon nom en français', $this->translationHelper->getTranslatedContentName($content, 'eng-US'));
    }

    /**
     * @dataProvider getTranslatedFieldProvider
     *
     * @param array<string> $prioritizedLanguages
     * @param string $expectedLocale
     */
    public function getTranslatedField(array $prioritizedLanguages, string $expectedLocale): void
    {
        $content = $this->generateContent();
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('languages')
            ->willReturn($prioritizedLanguages);

        self::assertSame($this->translatedFields[$expectedLocale], $this->translationHelper->getTranslatedField($content, 'test'));
    }

    /**
     * @phpstan-return list<array{array<string>, string}>
     */
    public function getTranslatedFieldProvider(): array
    {
        return $this->getTranslatedNameProvider();
    }

    public function testGetTranslationSiteAccessUnkownLanguage(): void
    {
        $this->configResolver
            ->expects(self::exactly(2))
            ->method('getParameter')
            ->willReturnMap(
                [
                    ['translation_siteaccesses', null, null, []],
                    ['related_siteaccesses', null, null, []],
                ]
            );

        $this->logger
            ->expects(self::once())
            ->method('error');

        self::assertNull($this->translationHelper->getTranslationSiteAccess('eng-DE'));
    }

    /**
     * @dataProvider getTranslationSiteAccessProvider
     *
     * @param string[] $translationSiteAccesses
     * @param string[] $relatedSiteAccesses
     */
    public function testGetTranslationSiteAccess(
        string $language,
        array $translationSiteAccesses,
        array $relatedSiteAccesses,
        ?string $expectedResult
    ): void {
        $this->configResolver
            ->expects(self::exactly(2))
            ->method('getParameter')
            ->willReturnMap(
                [
                    ['translation_siteaccesses', null, null, $translationSiteAccesses],
                    ['related_siteaccesses', null, null, $relatedSiteAccesses],
                ]
            );

        self::assertSame($expectedResult, $this->translationHelper->getTranslationSiteAccess($language));
    }

    /**
     * @phpstan-return list<array{string, array<string>, array<string>, string|null}>
     */
    public function getTranslationSiteAccessProvider(): array
    {
        return [
            ['eng-GB', ['fre', 'eng', 'heb'], ['esl', 'fre', 'eng', 'heb'], 'eng'],
            ['eng-GB', [], ['esl', 'fre', 'eng', 'heb'], 'eng'],
            ['eng-GB', [], ['esl', 'fre', 'eng', 'heb', 'my_siteaccess'], 'my_siteaccess'],
            ['eng-GB', ['esl', 'fre', 'eng', 'heb', 'my_siteaccess'], [], 'my_siteaccess'],
            ['eng-GB', ['esl', 'fre', 'eng', 'heb'], [], 'eng'],
            ['fre-FR', ['esl', 'fre', 'eng', 'heb'], [], 'fre'],
            ['fre-FR', ['esl', 'eng', 'heb'], [], null],
            ['heb-IL', ['esl', 'eng'], [], null],
            ['esl-ES', [], ['esl', 'mex', 'fre', 'eng', 'heb'], 'esl'],
            ['esl-ES', [], ['mex', 'fre', 'eng', 'heb'], 'mex'],
            ['esl-ES', ['esl', 'mex', 'fre', 'eng', 'heb'], [], 'esl'],
            ['esl-ES', ['mex', 'fre', 'eng', 'heb'], [], 'mex'],
        ];
    }

    public function testGetAvailableLanguagesWithTranslationSiteAccesses(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap(
                [
                    ['translation_siteaccesses', null, null, ['fre', 'esl']],
                    ['related_siteaccesses', null, null, ['fre', 'esl', 'heb']],
                    ['languages', null, null, ['eng-GB']],
                    ['languages', null, 'fre', ['fre-FR', 'eng-GB']],
                    ['languages', null, 'esl', ['esl-ES', 'fre-FR', 'eng-GB']],
                ]
            );

        $expectedLanguages = ['eng-GB', 'esl-ES', 'fre-FR'];
        self::assertSame($expectedLanguages, $this->translationHelper->getAvailableLanguages());
    }

    public function testGetAvailableLanguagesWithoutTranslationSiteAccesses(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap(
                [
                    ['translation_siteaccesses', null, null, []],
                    ['related_siteaccesses', null, null, ['fre', 'esl', 'heb']],
                    ['languages', null, null, ['eng-GB']],
                    ['languages', null, 'fre', ['fre-FR', 'eng-GB']],
                    ['languages', null, 'esl', ['esl-ES', 'fre-FR', 'eng-GB']],
                    ['languages', null, 'heb', ['heb-IL', 'eng-GB']],
                ]
            );

        $expectedLanguages = ['eng-GB', 'esl-ES', 'fre-FR', 'heb-IL'];
        self::assertSame($expectedLanguages, $this->translationHelper->getAvailableLanguages());
    }
}
