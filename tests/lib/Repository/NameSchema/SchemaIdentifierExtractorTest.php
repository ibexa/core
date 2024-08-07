<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\NameSchema;

use Ibexa\Contracts\Core\Repository\NameSchema\SchemaIdentifierExtractorInterface;
use Ibexa\Core\Repository\NameSchema\SchemaIdentifierExtractor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\NameSchema\SchemaIdentifierExtractor
 */
final class SchemaIdentifierExtractorTest extends TestCase
{
    private SchemaIdentifierExtractorInterface $extractor;

    /**
     * @return iterable<string, array{string, array<string, array<string>>}>
     */
    public function getDataForTestExtract(): iterable
    {
        $schemaString = '<short_name|name>';
        yield $schemaString => [
            $schemaString,
            [
                'field' => ['short_name', 'name'],
            ],
        ];

        $schemaString = '<custom_strategy:foo|field:bar>';
        yield $schemaString => [
            $schemaString,
            [
                'custom_strategy' => ['foo'],
                'field' => ['bar'],
            ],
        ];

        $schemaString = '<custom_strategy:bar|baz>';
        yield $schemaString => [
            $schemaString,
            [
                'custom_strategy' => ['bar'],
                'field' => ['baz'],
            ],
        ];

        $schemaString = '<custom_strategy:foo>-<field:bar>';
        yield $schemaString => [
            $schemaString,
            [
                'custom_strategy' => ['foo'],
                'field' => ['bar'],
            ],
        ];

        $schemaString = '<custom_strategy:foo|custom_strategy:bar>-<field:bar|baz>';
        yield $schemaString => [
            $schemaString,
            [
                'custom_strategy' => ['foo', 'bar'],
                'field' => ['bar', 'baz'],
            ],
        ];

        $schemaString = '<specification|(<name> <image1>)-<custom:bar|baz>-<field:bar|baz>';
        yield $schemaString => [
            $schemaString,
            [
                'field' => ['specification', 'name', 'image1', 'baz', 'bar'],
                'custom' => ['bar'],
            ],
        ];

        $schemaString = '<specification|(<name> <image1>)-(<custom:bar(|baz|bar)>)-<field:bar|baz>';
        yield $schemaString => [
            $schemaString,
            [
                'field' => ['specification', 'name', 'image1', 'baz', 'bar'],
                'custom' => ['bar'],
            ],
        ];

        $schemaString = '<description|(<attribute:mouse_type> <attribute:mouse_weight>)>';
        yield $schemaString => [
            $schemaString,
            [
                'field' => ['description'],
                'attribute' => ['mouse_type', 'mouse_weight'],
            ],
        ];

        $schemaString = '<abc|(<xyz> <name>)><abc|(<attribute:color> <attribute:color>)>';
        yield $schemaString => [
            $schemaString,
            [
                'field' => ['abc', 'xyz', 'name'],
                'attribute' => ['color'],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->extractor = new SchemaIdentifierExtractor();
    }

    /**
     * @dataProvider getDataForTestExtract
     *
     * @param array<string, array<string>> $expectedStrategyIdentifierMap
     */
    public function testExtract(string $schemaString, array $expectedStrategyIdentifierMap): void
    {
        $extracted = $this->extractor->extract($schemaString);
        self::assertSame($expectedStrategyIdentifierMap, $extracted);
    }
}
