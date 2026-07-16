<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Strategy\Publication;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Strategy\ContentPublication\SynchronousContentPublicationStrategy;
use PHPUnit\Framework\TestCase;

final class SynchronousContentPublicationStrategyTest extends TestCase
{
    public function testSupportsAlwaysReturnsTrue(): void
    {
        $strategy = new SynchronousContentPublicationStrategy(
            $this->createStub(ContentService::class)
        );

        self::assertTrue($strategy->supports());
    }

    /**
     * @dataProvider providerForTestPublishVersionDelegatesToContentService
     *
     * @param list<string>|null $translations
     * @param list<string> $expectedTranslations
     */
    public function testPublishVersionDelegatesToContentService(
        ?array $translations,
        array $expectedTranslations
    ): void {
        $versionInfo = $this->createStub(VersionInfo::class);

        $contentService = $this->createMock(ContentService::class);
        $contentService
            ->expects(self::once())
            ->method('publishVersion')
            ->with(self::identicalTo($versionInfo), $expectedTranslations);

        $publishArguments = ['versionInfo' => $versionInfo];
        if (null !== $translations) {
            $publishArguments['translations'] = $translations;
        }

        (new SynchronousContentPublicationStrategy($contentService))
            ->publishVersion(...$publishArguments);
    }

    /**
     * @return iterable<string, array{list<string>|null, list<string>}>
     */
    public static function providerForTestPublishVersionDelegatesToContentService(): iterable
    {
        yield 'explicit translations' => [['ger-DE'], ['ger-DE']];
        yield 'defaults to all translations' => [null, Language::ALL];
    }
}
