<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\TransformationProcessor;

use Ibexa\Core\Persistence;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

final class TransformationProcessorDefinitionBasedParserTest extends TestCase
{
    /**
     * @phpstan-return iterable<string, array{non-empty-string}>
     */
    public static function getTestFiles(): iterable
    {
        $ruleFiles = glob(dirname(__DIR__, 4) . '/src/lib/Resources/slug_converter/transformations/*.tr');
        self::assertNotFalse($ruleFiles, 'Failed to find transformation files');

        foreach ($ruleFiles as $file) {
            $filePath = realpath($file);
            self::assertNotFalse($filePath, "File $file does not exist");
            yield basename($filePath) => [$filePath];
        }
    }

    /**
     * @dataProvider getTestFiles
     */
    public function testParse(string $file): void
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser();

        $fixture = require $file . '.result.php';
        self::assertEquals(
            $fixture,
            $parser->parse($file)
        );
    }
}
