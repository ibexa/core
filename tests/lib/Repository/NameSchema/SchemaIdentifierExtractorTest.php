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
                'custom_strategy' => ['foo'],
                'field' => ['bar'],
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
        $this->markTestIncomplete('Requires fixing SchemaIdentifierExtractor behavior');

        self::assertSame($expectedStrategyIdentifierMap, $this->extractor->extract($schemaString));
    }
}
