<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition as PersistenceFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\CheckboxConverter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(CheckboxConverter::class)]
class CheckboxTest extends TestCase
{
    /** @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\CheckboxConverter */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new CheckboxConverter();
    }

    #[Group('fieldType')]
    #[Group('ibexa_boolean')]
    public function testToStorageValue(): void
    {
        $value = new FieldValue();
        $value->data = true;
        $value->sortKey = 1;
        $storageFieldValue = new StorageFieldValue();

        $this->converter->toStorageValue($value, $storageFieldValue);
        self::assertSame((int)$value->data, $storageFieldValue->dataInt);
        self::assertSame($value->sortKey, $storageFieldValue->sortKeyInt);
        self::assertSame('', $storageFieldValue->sortKeyString);
    }

    #[Group('fieldType')]
    #[Group('ibexa_boolean')]
    public function testToFieldValue(): void
    {
        $storageFieldValue = new StorageFieldValue();
        $storageFieldValue->dataInt = 1;
        $storageFieldValue->sortKeyInt = 1;
        $storageFieldValue->sortKeyString = '';
        $fieldValue = new FieldValue();

        $this->converter->toFieldValue($storageFieldValue, $fieldValue);
        self::assertSame((bool)$storageFieldValue->dataInt, $fieldValue->data);
        self::assertSame($storageFieldValue->sortKeyInt, $fieldValue->sortKey);
    }

    #[Group('fieldType')]
    #[Group('ibexa_boolean')]
    public function testToStorageFieldDefinition(): void
    {
        $defaultBool = false;
        $storageFieldDef = new StorageFieldDefinition();
        $defaultValue = new FieldValue();
        $defaultValue->data = $defaultBool;
        $fieldDef = new PersistenceFieldDefinition(
            [
                'defaultValue' => $defaultValue,
            ]
        );

        $this->converter->toStorageFieldDefinition($fieldDef, $storageFieldDef);
        self::assertSame(
            (int)$fieldDef->defaultValue->data,
            $storageFieldDef->dataInt3
        );
    }

    #[Group('fieldType')]
    #[Group('ibexa_boolean')]
    public function testToFieldDefinition(): void
    {
        $defaultBool = true;
        $fieldDef = new PersistenceFieldDefinition();
        $storageDef = new StorageFieldDefinition(
            [
                'dataInt3' => 1,
            ]
        );

        $this->converter->toFieldDefinition($storageDef, $fieldDef);
        self::assertSame($defaultBool, $fieldDef->defaultValue->data);
        self::assertNull($fieldDef->fieldTypeConstraints->fieldSettings);
    }
}
