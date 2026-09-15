<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Limitation\LanguageLimitation;

use Ibexa\Contracts\Core\Limitation\Target;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Core\Limitation\LanguageLimitation\ContentDeleteEvaluator;
use PHPUnit\Framework\TestCase;

final class ContentDeleteEvaluatorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderForAccept')]
    public function testAccept(Target\Version $targetVersion, bool $expected): void
    {
        self::assertSame(
            $expected,
            (new ContentDeleteEvaluator())->accept($targetVersion)
        );
    }

    public static function dataProviderForAccept(): iterable
    {
        yield [
            self::getTergetVersion(['eng-GB', 'ger-DE']),
            true,
        ];

        yield [
            self::getTergetVersion([]),
            false,
        ];

        yield [
            new Target\Version(),
            false,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderForEvaluate')]
    public function testEvaluate(Target\Version $targetVersion, Limitation $limitationValue, bool $expected): void
    {
        self::assertSame(
            $expected,
            (new ContentDeleteEvaluator())->evaluate($targetVersion, $limitationValue)
        );
    }

    public static function dataProviderForEvaluate(): iterable
    {
        yield 'same_values' => [
            self::getTergetVersion(['eng-GB', 'ger-DE']),
            self::getLanguageLimitation(['eng-GB', 'ger-DE']),
            true,
        ];

        yield 'missing_fr_limitation' => [
            self::getTergetVersion(['eng-GB', 'ger-DE', 'fre-FR']),
            self::getLanguageLimitation(['eng-GB', 'ger-DE']),
            false,
        ];

        yield 'extra_fr_limitation' => [
            self::getTergetVersion(['eng-GB', 'ger-DE']),
            self::getLanguageLimitation(['eng-GB', 'ger-DE', 'fre-FR']),
            true,
        ];

        yield 'separable_values' => [
            self::getTergetVersion(['eng-GB']),
            self::getLanguageLimitation(['fre-FR']),
            false,
        ];
    }

    private static function getTergetVersion(array $languageCodes): Target\Version
    {
        return (new Target\Version())->deleteTranslations($languageCodes);
    }

    private static function getLanguageLimitation(array $languageCodes): Limitation\LanguageLimitation
    {
        return new Limitation\LanguageLimitation(['limitationValues' => $languageCodes]);
    }
}
