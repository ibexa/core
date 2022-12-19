<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Core\Limitation\LimitationIdentifierToLabelConverter;
use PHPUnit\Framework\TestCase;

final class LimitationIdentifierToLabelConverterTest extends TestCase
{
    public function testConvert(): void
    {
        $label = LimitationIdentifierToLabelConverter::convert('Some_Identifier');

        self::assertSame('policy.limitation.identifier.some_identifier', $label);
    }
}
