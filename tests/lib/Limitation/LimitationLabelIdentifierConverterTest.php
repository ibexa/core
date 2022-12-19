<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Core\Limitation\LimitationLabelIdentifierConverter;
use PHPUnit\Framework\TestCase;

class LimitationLabelIdentifierConverterTest extends TestCase
{
    public function testConvert(): void
    {
        $label = LimitationLabelIdentifierConverter::convert('Some_Identifier');

        self::assertEquals('policy.limitation.identifier.some_identifier', $label);
    }
}
