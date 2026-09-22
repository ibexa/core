<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException;
use Ibexa\Contracts\Core\Repository\Values\Content\Section;
use Ibexa\Tests\Core\Repository\Values\ValueObjectTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Section::class)]
class SectionTest extends TestCase
{
    use ValueObjectTestTrait;

    /**
     * Test retrieving missing property.
     */
    public function testMissingProperty(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $section = new Section();
        /** @phpstan-ignore-next-line property.notFound */
        $value = $section->notDefined;
        self::fail('Succeeded getting non existing property');
    }

    /**
     * Test setting read only property.
     */
    public function testReadOnlyProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $section = new Section();
        $section->id = 22;
        self::fail('Succeeded setting read only property');
    }

    /**
     * Test if property exists.
     */
    public function testIsPropertySet(): void
    {
        $section = new Section();
        /** @phpstan-ignore property.notFound */
        $value = isset($section->notDefined);
        self::assertFalse($value);

        $value = isset($section->id);
        self::assertTrue($value);
    }

    /**
     * Test unsetting a property.
     */
    public function testUnsetProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $section = new Section(['id' => 1]);
        unset($section->id);
        self::fail('Unsetting read-only property succeeded');
    }
}
