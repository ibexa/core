<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\UrlAlias;

use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\Core\Persistence\TransformationProcessor\PcreCompiler;
use Ibexa\Core\Persistence\TransformationProcessor\PreprocessedBased;
use Ibexa\Core\Persistence\Utf8Converter;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter
 */
final class SlugConverterTest extends TestCase
{
    /** @var array<string, mixed> */
    protected array $configuration = [
        'transformation' => 'testTransformation1',
        'transformationGroups' => [
            'testTransformation1' => [
                'commands' => [
                    'test_command1',
                ],
                'cleanupMethod' => 'test_cleanup1',
            ],
            'testTransformation2' => [
                'commands' => [
                    'test_command2',
                ],
                'cleanupMethod' => 'test_cleanup2',
            ],
        ],
        'reservedNames' => [
            'reserved',
        ],
    ];

    protected SlugConverter $slugConverter;

    protected SlugConverter & MockObject $slugConverterMock;

    protected TransformationProcessor & MockObject $transformationProcessorMock;

    public function testConvert(): void
    {
        $slug = 'test_text_c';
        $text = 'test text  č ';
        $slugConverter = $this->mockSlugConverter($slug, $text);

        self::assertEquals(
            $slug,
            $slugConverter->convert($text)
        );
    }

    public function testConvertWithDefaultTextFallback(): void
    {
        $slug = 'test_text_c';
        $defaultText = 'test text  č ';

        $slugConverter = $this->mockSlugConverter($slug, $defaultText);

        self::assertEquals(
            $slug,
            $slugConverter->convert('', $defaultText)
        );
    }

    /**
     * Test for the convert() method.
     */
    public function testConvertWithGivenTransformation(): void
    {
        $slugConverter = $this->getSlugConverterMock(['cleanupText']);
        $transformationProcessor = $this->getTransformationProcessorMock();

        $text = 'test text  č ';
        $transformedText = 'test text  c ';
        $slug = 'test_text_c';

        $transformationProcessor->expects(self::atLeastOnce())
            ->method('transform')
            ->with($text, ['test_command2'])
            ->willReturn($transformedText);

        $slugConverter->expects(self::once())
            ->method('cleanupText')
            ->with(self::equalTo($transformedText), self::equalTo('test_cleanup2'))
            ->willReturn($slug);

        self::assertEquals(
            $slug,
            $slugConverter->convert($text, '_1', 'testTransformation2')
        );
    }

    /**
     * @return iterable<array{0: string, 1: bool, 2: int}>
     */
    public static function providerForTestGetUniqueCounterValue(): iterable
    {
        yield ['reserved', true, 2];
        yield ['reserved', false, 1];
        yield ['not-reserved', true, 1];
        yield ['not-reserved', false, 1];
    }

    /**
     * Test for the getUniqueCounterValue() method.
     *
     * @dataProvider providerForTestGetUniqueCounterValue
     */
    public function testGetUniqueCounterValue(string $text, bool $isRootLevel, int $returnValue): void
    {
        $slugConverter = $this->getSlugConverter();

        self::assertEquals(
            $returnValue,
            $slugConverter->getUniqueCounterValue($text, $isRootLevel)
        );
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string}>
     */
    public static function cleanupTextData(): iterable
    {
        yield [
                '.Ph\'nglui mglw\'nafh, Cthulhu R\'lyeh wgah\'nagl fhtagn!?...',
                'url_cleanup',
                'Ph-nglui-mglw-nafh-Cthulhu-R-lyeh-wgah-nagl-fhtagn!',
        ];
        yield [
                '.Ph\'nglui mglw\'nafh, Cthulhu R\'lyeh wgah\'nagl fhtagn!?...',
                'url_cleanup_iri',
                'Ph\'nglui-mglw\'nafh,-Cthulhu-R\'lyeh-wgah\'nagl-fhtagn!',
        ];
        yield [
                '.Ph\'nglui mglw\'nafh, Cthulhu R\'lyeh wgah\'nagl fhtagn!?...',
                'url_cleanup_compat',
                'ph_nglui_mglw_nafh_cthulhu_r_lyeh_wgah_nagl_fhtagn',
        ];
    }

    /**
     * Test for the cleanupText() method.
     *
     * @dataProvider cleanupTextData
     */
    public function testCleanupText(string $text, string $method, string $expected): void
    {
        $testMethod = new ReflectionMethod(
            SlugConverter::class,
            'cleanupText'
        );
        $testMethod->setAccessible(true);

        $actual = $testMethod->invoke($this->getSlugConverter(), $text, $method);

        self::assertEquals(
            $expected,
            $actual
        );
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function convertData(): iterable
    {
        yield [
                '.Ph\'nglui mglw\'nafh, Cthulhu R\'lyeh wgah\'nagl fhtagn!?...',
                '\'_1\'',
                'urlalias',
                'Ph-nglui-mglw-nafh-Cthulhu-R-lyeh-wgah-nagl-fhtagn!',
        ];
        yield [
                '.Ph\'nglui mglw\'nafh, Cthulhu R\'lyeh wgah\'nagl fhtagn!?...',
                '\'_1\'',
                'urlalias_iri',
                'Ph\'nglui-mglw\'nafh,-Cthulhu-R\'lyeh-wgah\'nagl-fhtagn!',
        ];
        yield [
                '.Ph\'nglui mglw\'nafh, Cthulhu R\'lyeh wgah\'nagl fhtagn!?...',
                '\'_1\'',
                'urlalias_compat',
                'ph_nglui_mglw_nafh_cthulhu_r_lyeh_wgah_nagl_fhtagn',
        ];
    }

    /**
     * @dataProvider convertData
     *
     * @depends testCleanupText
     */
    public function testConvertNoMocking(string $text, string $defaultText, string $transformation, string $expected): void
    {
        $transformationsDirectory = dirname(__DIR__, 6) . '/src/lib/Resources/slug_converter/transformations';
        $transformationProcessor = new PreprocessedBased(
            new PcreCompiler(
                new Utf8Converter()
            ),
            [
                "$transformationsDirectory/ascii.tr.result.php",
                "$transformationsDirectory/basic.tr.result.php",
                "$transformationsDirectory/latin.tr.result.php",
                "$transformationsDirectory/search.tr.result.php",
            ]
        );
        $slugConverter = new SlugConverter($transformationProcessor);

        self::assertEquals(
            $expected,
            $slugConverter->convert($text, $defaultText, $transformation)
        );
    }

    protected function getSlugConverter(): SlugConverter
    {
        if (!isset($this->slugConverter)) {
            $this->slugConverter = new SlugConverter(
                $this->getTransformationProcessorMock(),
                $this->configuration
            );
        }

        return $this->slugConverter;
    }

    /**
     * @param string[] $methods
     */
    protected function getSlugConverterMock(array $methods = []): SlugConverter & MockObject
    {
        if (!isset($this->slugConverterMock)) {
            $this->slugConverterMock = $this->getMockBuilder(SlugConverter::class)
                ->onlyMethods($methods)
                ->setConstructorArgs(
                    [
                        $this->getTransformationProcessorMock(),
                        $this->configuration,
                    ]
                )
                ->getMock();
        }

        return $this->slugConverterMock;
    }

    protected function getTransformationProcessorMock(): TransformationProcessor & MockObject
    {
        if (!isset($this->transformationProcessorMock)) {
            $this->transformationProcessorMock = $this->getMockForAbstractClass(
                TransformationProcessor::class,
                [],
                '',
                false,
                true,
                true,
                ['transform']
            );
        }

        return $this->transformationProcessorMock;
    }

    private function mockSlugConverter(string $slug, string $defaultText): SlugConverter & MockObject
    {
        $slugConverter = $this->getSlugConverterMock(['cleanupText']);
        $transformationProcessor = $this->getTransformationProcessorMock();

        $transformedText = 'test text  c ';

        $transformationProcessor
            ->expects(self::atLeastOnce())
            ->method('transform')
            ->with($defaultText, ['test_command1'])
            ->willReturn($transformedText);

        $slugConverter
            ->expects(self::once())
            ->method('cleanupText')
            ->with(self::equalTo($transformedText), self::equalTo('test_cleanup1'))
            ->willReturn($slug);

        return $slugConverter;
    }
}
