<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Values\ObjectState;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException;
use Ibexa\Core\Repository\Values\ObjectState\ObjectStateGroup;
use Ibexa\Tests\Core\Repository\Values\MultiLanguageTestTrait;
use Ibexa\Tests\Core\Repository\Values\ValueObjectTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Values\ObjectState\ObjectStateGroup
 */
class ObjectStateGroupTest extends TestCase
{
    use ValueObjectTestTrait;
    use MultiLanguageTestTrait;

    /**
     * Test a new class and default values on properties.
     */
    public function testNewClass(): void
    {
        $objectStateGroup = new ObjectStateGroup();

        $this->assertPropertiesCorrect(
            [
                'id' => null,
                'identifier' => null,
                'mainLanguageCode' => null,
                'languageCodes' => null,
                'names' => [],
                'descriptions' => [],
            ],
            $objectStateGroup
        );
    }

    /**
     * Test a new class with unified multi language logic properties.
     *
     * @return \Ibexa\Core\Repository\Values\ObjectState\ObjectStateGroup
     */
    public function testNewClassWithMultiLanguageProperties(): ObjectStateGroup
    {
        $properties = [
            'names' => [
                'eng-US' => 'Name',
                'pol-PL' => 'Nazwa',
            ],
            'descriptions' => [
                'eng-US' => 'Description',
                'pol-PL' => 'Opis',
            ],
            'mainLanguageCode' => 'eng-US',
            'prioritizedLanguages' => ['pol-PL', 'eng-US'],
        ];

        $objectStateGroup = new ObjectStateGroup($properties);
        $this->assertPropertiesCorrect($properties, $objectStateGroup);

        // BC test:
        self::assertTrue(isset($objectStateGroup->defaultLanguageCode));
        self::assertSame('eng-US', $objectStateGroup->defaultLanguageCode);

        return $objectStateGroup;
    }

    /**
     * Test retrieving missing property.
     *
     * @covers \Ibexa\Core\Repository\Values\ObjectState\ObjectStateGroup::__get
     */
    public function testMissingProperty(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $objectStateGroup = new ObjectStateGroup();
        $value = $objectStateGroup->notDefined;
        self::fail('Succeeded getting non existing property');
    }

    /**
     * Test setting read only property.
     *
     * @covers \Ibexa\Core\Repository\Values\ObjectState\ObjectStateGroup::__set
     */
    public function testReadOnlyProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $objectStateGroup = new ObjectStateGroup();
        $objectStateGroup->id = 42;
        self::fail('Succeeded setting read only property');
    }

    /**
     * Test if property exists.
     */
    public function testIsPropertySet(): void
    {
        $objectStateGroup = new ObjectStateGroup();
        $value = isset($objectStateGroup->notDefined);
        self::assertFalse($value);

        $value = isset($objectStateGroup->id);
        self::assertTrue($value);
    }

    /**
     * Test unsetting a property.
     *
     * @covers \Ibexa\Core\Repository\Values\ObjectState\ObjectStateGroup::__unset
     */
    public function testUnsetProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $objectStateGroup = new ObjectStateGroup(['id' => 2]);
        unset($objectStateGroup->id);
        self::fail('Unsetting read-only property succeeded');
    }
}
