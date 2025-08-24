<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\VariationPathGenerator;

use Ibexa\Bundle\Core\Imagine\VariationPathGenerator\AliasDirectoryVariationPathGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\VariationPathGenerator\AliasDirectoryVariationPathGenerator
 */
final class AliasDirectoryVariationPathGeneratorTest extends TestCase
{
    public function testGetVariationPath(): void
    {
        $generator = new AliasDirectoryVariationPathGenerator();

        self::assertEquals(
            '_aliases/large/path/to/original.png',
            $generator->getVariationPath('path/to/original.png', 'large')
        );
    }
}
