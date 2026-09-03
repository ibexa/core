<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\FieldValue\Converter;

use DateTime;
use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition as PersistenceFieldDefinition;
use Ibexa\Core\FieldType\Date\Type as DateType;
use Ibexa\Core\FieldType\FieldSettings;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\DateConverter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\DateConverter
 *
 * @group fieldType
 * @group date
 */
class DateTest extends TestCase
{
    protected DateConverter $converter;

    protected DateTime $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new DateConverter();
        $this->date = new DateTime('@1362614400');
    }

    /**
     * @dataProvider providerForTestToStorageValue
     *
     * @param array<string, mixed>|null $data
     */
    public function testToStorageValue(
        ?array $data,
        int $sortKey,
        ?int $expectedDataInt,
        int $expectedSortKeyInt,
        ?string $expectedSortKeyString = null
    ): void {
        $value = new FieldValue();
        $value->data = $data;
        $value->sortKey = $sortKey;
        $storageFieldValue = new StorageFieldValue();

        $this->converter->toStorageValue($value, $storageFieldValue);
        self::assertSame($expectedDataInt, $storageFieldValue->dataInt);
        self::assertSame($expectedSortKeyInt, $storageFieldValue->sortKeyInt);

        if ($expectedSortKeyString !== null) {
            self::assertSame($expectedSortKeyString, $storageFieldValue->sortKeyString);
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>|null, int, int|null, int, string|null}>
     *
     * @throws \DateMalformedStringException
     */
    public static function providerForTestToStorageValue(): iterable
    {
        $timestamp = 1362614400;
        $date = new DateTime('@' . $timestamp);

        yield 'with timestamp and rfc850' => [
            [
                'timestamp' => $timestamp,
                'rfc850' => $date->format(DateTime::RFC850),
            ],
            2,
            $timestamp,
            2,
            '',
        ];

        yield 'with null data' => [
            null,
            0,
            null,
            0,
            null,
        ];

        yield 'with timestring only' => [
            [
                'rfc850' => null,
                'timestring' => '@' . $timestamp,
            ],
            $timestamp,
            $timestamp,
            $timestamp,
            null,
        ];

        yield 'without timestamp and timestring' => [
            [
                'rfc850' => null,
            ],
            0,
            null,
            0,
            null,
        ];

        yield 'with timestring only and missing sort key falls back to parsed timestamp' => [
            [
                'rfc850' => null,
                'timestring' => '@' . $timestamp,
            ],
            0,
            $timestamp,
            $timestamp,
            null,
        ];
    }

    public function testToFieldValue(): void
    {
        $storageFieldValue = new StorageFieldValue();
        $storageFieldValue->dataInt = $this->date->getTimestamp();
        $storageFieldValue->sortKeyString = '';
        $storageFieldValue->sortKeyInt = $this->date->getTimestamp();
        $fieldValue = new FieldValue();

        $this->converter->toFieldValue($storageFieldValue, $fieldValue);
        self::assertSame(
            [
                'timestamp' => $this->date->getTimestamp(),
                'rfc850' => null,
            ],
            $fieldValue->data
        );
        self::assertSame($storageFieldValue->sortKeyInt, $fieldValue->sortKey);
    }

    /**
     * @dataProvider providerForTestToStorageFieldDefinition
     */
    public function testToStorageFieldDefinition(int $defaultType): void
    {
        $storageFieldDef = new StorageFieldDefinition();
        $fieldTypeConstraints = new FieldTypeConstraints();
        $fieldTypeConstraints->fieldSettings = new FieldSettings(
            [
                'defaultType' => $defaultType,
            ]
        );
        $fieldDef = new PersistenceFieldDefinition(
            [
                'fieldTypeConstraints' => $fieldTypeConstraints,
            ]
        );

        $this->converter->toStorageFieldDefinition($fieldDef, $storageFieldDef);
        self::assertSame(
            $defaultType,
            $storageFieldDef->dataInt1
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function providerForTestToStorageFieldDefinition(): iterable
    {
        yield 'default empty' => [DateType::DEFAULT_EMPTY];
        yield 'default current date' => [DateType::DEFAULT_CURRENT_DATE];
    }

    public function testToFieldDefinitionDefaultEmpty(): void
    {
        $fieldDef = new PersistenceFieldDefinition();
        $storageDef = new StorageFieldDefinition(
            [
                'dataInt1' => DateType::DEFAULT_EMPTY,
            ]
        );

        $this->converter->toFieldDefinition($storageDef, $fieldDef);
        self::assertNull($fieldDef->defaultValue->data);
    }

    public function testToFieldDefinitionDefaultCurrentDate(): void
    {
        $fieldDef = new PersistenceFieldDefinition();
        $storageDef = new StorageFieldDefinition(
            [
                'dataInt1' => DateType::DEFAULT_CURRENT_DATE,
            ]
        );

        $this->converter->toFieldDefinition($storageDef, $fieldDef);
        self::assertIsArray($fieldDef->defaultValue->data);
        self::assertCount(2, $fieldDef->defaultValue->data);
        self::assertNull($fieldDef->defaultValue->data['rfc850']);
        self::assertSame('now', $fieldDef->defaultValue->data['timestring']);
    }
}
