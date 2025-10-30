<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Tests\Core\Repository\Values\ValueObjectTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Values\Content\Location
 */
final class LocationTest extends TestCase
{
    use ValueObjectTestTrait;

    /**
     * Test a new class and default values on properties.
     */
    public function testNewClass(): void
    {
        $location = new Location();

        $this->assertPropertiesCorrect(
            [
                'id' => null,
                'contentInfo' => null,
                'priority' => null,
                'hidden' => null,
                'invisible' => null,
                'remoteId' => null,
                'parentLocationId' => null,
                'pathString' => null,
                'path' => [],
                'depth' => null,
                'sortField' => null,
                'sortOrder' => null,
            ],
            $location
        );
    }

    public function testStrictGetters(): void
    {
        $location = new Location(
            [
                'id' => 123,
                'contentInfo' => new ContentInfo(['id' => 456]),
                'hidden' => true,
                'depth' => 3,
                'pathString' => '/1/2/123/',
            ]
        );

        self::assertSame(123, $location->getId());
        self::assertSame(456, $location->getContentId());
        self::assertTrue($location->isHidden());
        self::assertSame(3, $location->getDepth());
        self::assertSame('/1/2/123/', $location->getPathString());
    }

    /**
     * @return iterable<string, array{\Ibexa\Core\Repository\Values\Content\Location, string[]}>
     */
    public static function getDataForTestPathComputedPropertyGetter(): iterable
    {
        yield 'nested path' => [
            new Location(['id' => 3, 'pathString' => '/1/2/3/']),
            ['1', '2', '3'],
        ];

        yield 'nested path no trailing slash' => [
            new Location(['id' => 4, 'pathString' => '/1/2/4']),
            ['1', '2', '4'],
        ];

        yield 'root element' => [
            new Location(['id' => 1, 'pathString' => '/1/']),
            ['1'],
        ];

        yield 'malformed path' => [
            new Location(['id' => 1, 'pathString' => '/']),
            [],
        ];

        yield 'empty path' => [
            new Location(['id' => 1, 'pathString' => '']),
            [],
        ];

        yield 'null path' => [
            new Location(['id' => 1, 'pathString' => null]),
            [],
        ];
    }

    /**
     * @dataProvider getDataForTestPathComputedPropertyGetter
     *
     * @param string[] $expectedPathValue
     */
    public function testPathComputedPropertyGetter(Location $location, array $expectedPathValue): void
    {
        self::assertSame($expectedPathValue, $location->getPath());
    }

    /**
     * Test retrieving missing property.
     */
    public function testMissingProperty(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $location = new Location();
        /** @phpstan-ignore-next-line property.notFound */
        $value = $location->notDefined;
        self::fail('Succeeded getting non existing property');
    }

    /**
     * Test setting read only property.
     *
     * @covers \Ibexa\Core\Repository\Values\Content\Location::__set
     */
    public function testReadOnlyProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $location = new Location();
        $location->id = 42;
        self::fail('Succeeded setting read only property');
    }

    /**
     * Test if property exists.
     */
    public function testIsPropertySet(): void
    {
        $location = new Location();
        /** @phpstan-ignore property.notFound */
        $value = isset($location->notDefined);
        self::assertFalse($value);

        $value = isset($location->id);
        self::assertTrue($value);
    }

    /**
     * Test unsetting a property.
     *
     * @covers \Ibexa\Core\Repository\Values\Content\Location::__unset
     */
    public function testUnsetProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $location = new Location(['id' => 2]);
        unset($location->id);
        self::fail('Unsetting read-only property succeeded');
    }
}

class_alias(LocationTest::class, 'eZ\Publish\Core\Repository\Tests\Values\Content\LocationTest');
