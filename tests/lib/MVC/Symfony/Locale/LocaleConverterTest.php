<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Locale;

use Ibexa\Core\MVC\Symfony\Locale\LocaleConverter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Locale\LocaleConverter
 */
final class LocaleConverterTest extends TestCase
{
    private LocaleConverter $localeConverter;

    /** @var \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private LoggerInterface $logger;

    /**
     * @var array{
     *   array{
     *     string,
     *     string|null,
     *   }
     * }
     */
    private array $conversionMap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conversionMap = [
            'eng-GB' => 'en_GB',
            'eng-US' => 'en_US',
            'fre-FR' => 'fr_FR',
            'ger-DE' => 'de_DE',
            'nor-NO' => 'no_NO',
            'cro-HR' => 'hr_HR',
        ];

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->localeConverter = new LocaleConverter($this->conversionMap, $this->logger);
    }

    /**
     * @dataProvider convertToPOSIXProvider
     */
    public function testConvertToPOSIX(string $repositoryLocale, ?string $expected): void
    {
        if ($expected === null) {
            $this->logger
                ->expects(self::once())
                ->method('warning');
        }

        self::assertSame($expected, $this->localeConverter->convertToPOSIX($repositoryLocale));
    }

    public function convertToPOSIXProvider(): array
    {
        return [
            ['eng-GB', 'en_GB'],
            ['eng-US', 'en_US'],
            ['fre-FR', 'fr_FR'],
            ['chi-CN', null],
            ['epo-EO', null],
            ['nor-NO', 'no_NO'],
        ];
    }

    /**
     * @dataProvider convertToRepositoryProvider
     */
    public function testConvertToRepository(string $posixLocale, ?string $expected): void
    {
        if ($expected === null) {
            $this->logger
                ->expects(self::once())
                ->method('warning');
        }

        self::assertSame($expected, $this->localeConverter->convertToRepository($posixLocale));
    }

    /**
     * @return array{
     *   array{
     *     string,
     *     string|null,
     *   }
     * }
     */
    public function convertToRepositoryProvider(): array
    {
        return [
            ['en_GB', 'eng-GB'],
            ['en_US', 'eng-US'],
            ['fr_FR', 'fre-FR'],
            ['zh-CN', null],
            ['eo', null],
            ['no_NO', 'nor-NO'],
        ];
    }
}
