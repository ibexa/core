<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Values\ObjectState;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup as CoveredObjectStateGroup;
use Ibexa\Core\Repository\Values\ObjectState\ObjectState;
use Ibexa\Core\Repository\Values\ObjectState\ObjectStateGroup;
use Ibexa\Tests\Core\Repository\Values\MultiLanguageTestTrait;
use Ibexa\Tests\Core\Repository\Values\ValueObjectTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObjectState::class)]
#[CoversMethod(ObjectState::class, '__get')]
#[CoversMethod(ObjectStateGroup::class, '__get')]
#[CoversMethod(ObjectState::class, '__set')]
#[CoversMethod(ObjectStateGroup::class, '__set')]
#[CoversMethod(ObjectState::class, '__unset')]
#[CoversMethod(CoveredObjectStateGroup::class, '__unset')]
class ObjectStateTest extends TestCase
{
    use ValueObjectTestTrait;
    use MultiLanguageTestTrait;

    /**
     * Test a new class and default values on properties.
     */
    public function testNewClass()
    {
        $objectState = new ObjectState();

        $this->assertPropertiesCorrect(
            [
                'id' => null,
                'identifier' => null,
                'priority' => null,
                'mainLanguageCode' => null,
                'languageCodes' => null,
                'names' => [],
                'descriptions' => [],
            ],
            $objectState
        );
    }

    /**
     * Test a new class with unified multi language logic properties.
     *
     * @return \Ibexa\Core\Repository\Values\ObjectState\ObjectState
     */
    public function testNewClassWithMultiLanguageProperties()
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

        $objectState = new ObjectState($properties);
        $this->assertPropertiesCorrect($properties, $objectState);

        // BC test:
        self::assertTrue(isset($objectState->defaultLanguageCode));
        self::assertSame('eng-US', $objectState->defaultLanguageCode);

        return $objectState;
    }

    /**
     * Test retrieving missing property.
     */
    public function testMissingProperty()
    {
        $this->expectException(PropertyNotFoundException::class);

        $objectState = new ObjectState();
        /** @phpstan-ignore property.notFound */
        $value = $objectState->notDefined;
        self::fail('Succeeded getting non existing property');
    }

    /**
     * Test setting read only property.
     */
    public function testReadOnlyProperty()
    {
        $this->expectException(PropertyReadOnlyException::class);

        $objectState = new ObjectState();
        $objectState->id = 42;
        self::fail('Succeeded setting read only property');
    }

    /**
     * Test if property exists.
     */
    public function testIsPropertySet()
    {
        $objectState = new ObjectState();
        /** @phpstan-ignore property.notFound */
        $value = isset($objectState->notDefined);
        self::assertFalse($value);

        $value = isset($objectState->id);
        self::assertTrue($value);
    }

    /**
     * Test unsetting a property.
     */
    public function testUnsetProperty()
    {
        $this->expectException(PropertyReadOnlyException::class);

        $objectState = new ObjectState(['id' => 2]);
        unset($objectState->id);
        self::fail('Unsetting read-only property succeeded');
    }
}
