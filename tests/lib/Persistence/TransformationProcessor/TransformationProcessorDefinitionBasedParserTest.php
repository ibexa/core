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
        $ruleFiles = glob(dirname(__DIR__, 4) . '/src/lib/Resources/slug_converter/transformations/*.tr');
        self::assertNotFalse($ruleFiles, 'Failed to find transformation files');

        return array_map(
            static fn (string $file): array => [realpath($file)],
            $ruleFiles
        );
    }

    /**
     * @dataProvider getTestFiles
     */
    public function testParse($file)
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser();

        $fixture = include $file . '.result.php';
        self::assertEquals(
            $fixture,
            $parser->parse($file)
        );
    }
}
