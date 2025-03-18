<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\TransformationProcessor;

use Ibexa\Core\Persistence;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * Test case for LocationHandlerTest.
 */
class TransformationProcessorDefinitionBasedParserTest extends TestCase
{
    /**
     * @phpstan-return array<array{non-empty-string|false}>
     */
    public static function getTestFiles(): array
    {
        $glob = glob(__DIR__ . '/_fixtures/transformations/*.tr');

        return false !== $glob
            ? array_map(
                static function (string $file): array {
                    return [realpath($file)];
                },
                $glob
            )
            : [];
    }

    /**
     * @dataProvider getTestFiles
     */
    public function testParse(string $file): void
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser();

        $fixture = include $file . '.result';
        self::assertEquals(
            $fixture,
            $parser->parse($file)
        );
    }
}
