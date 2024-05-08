<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\FieldType\ValueSerializerInterface;
use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\FieldType\FieldSettings;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\SerializableConverter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\SerializableConverter
 */
class SerializableConverterTest extends TestCase
{
    private const EXAMPLE_DATA = [
        'foo' => 'foo',
        'bar' => 'bar',
    ];

    private const EXAMPLE_JSON = '{"foo":"foo","bar":"bar"}';

    /** @var \Ibexa\Contracts\Core\FieldType\ValueSerializerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\SerializableConverter */
    private $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->createMock(ValueSerializerInterface::class);
        $this->converter = new SerializableConverter($this->serializer);
    }

    public function testToStorageValue(): void
    {
        $fieldValue = new FieldValue();
        $fieldValue->data = self::EXAMPLE_DATA;
        $fieldValue->sortKey = 'key';

        $this->serializer
            ->expects(self::once())
            ->method('encode')
            ->with($fieldValue->data)
            ->willReturn(self::EXAMPLE_JSON);

        $storageValue = new StorageFieldValue();

        $this->converter->toStorageValue($fieldValue, $storageValue);

        self::assertEquals(self::EXAMPLE_JSON, $storageValue->dataText);
        self::assertEquals('key', $storageValue->sortKeyString);
    }

    public function testEmptyToStorageValue(): void
    {
        $this->serializer
            ->expects(self::never())
            ->method('encode');

        $storageValue = new StorageFieldValue();

        $this->converter->toStorageValue(new FieldValue(), $storageValue);

        self::assertNull($storageValue->dataText);
    }

    public function testToFieldValue(): void
    {
        $storageValue = new StorageFieldValue();
        $storageValue->sortKeyString = 'key';
        $storageValue->dataText = self::EXAMPLE_JSON;

        $this->serializer
            ->expects(self::once())
            ->method('decode')
            ->with(self::EXAMPLE_JSON)
            ->willReturn(self::EXAMPLE_DATA);

        $fieldValue = new FieldValue();

        $this->converter->toFieldValue($storageValue, $fieldValue);

        self::assertEquals('key', $fieldValue->sortKey);
        self::assertEquals(self::EXAMPLE_DATA, $fieldValue->data);
        self::assertNull($fieldValue->externalData);
    }

    public function testEmptyToFieldValue(): void
    {
        $this->serializer
            ->expects(self::never())
            ->method('decode');

        $fieldValue = new FieldValue();

        $this->converter->toFieldValue(new StorageFieldValue(), $fieldValue);

        self::assertNull($fieldValue->data);
    }

    public function testToStorageFieldDefinition(): void
    {
        $fieldTypeConstraints = new FieldTypeConstraints();
        $fieldTypeConstraints->fieldSettings = new FieldSettings(self::EXAMPLE_DATA);

        $fieldDefinition = new FieldDefinition([
            'fieldTypeConstraints' => $fieldTypeConstraints,
        ]);

        $this->serializer
            ->expects(self::once())
            ->method('encode')
            ->with(self::EXAMPLE_DATA)
            ->willReturn(self::EXAMPLE_JSON);

        $storageFieldDefinition = new StorageFieldDefinition();

        $this->converter->toStorageFieldDefinition($fieldDefinition, $storageFieldDefinition);

        self::assertEquals(self::EXAMPLE_JSON, $storageFieldDefinition->dataText5);
    }

    public function testEmptyToStorageFieldDefinition(): void
    {
        $this->serializer
            ->expects(self::never())
            ->method('encode');

        $storageFieldDefinition = new StorageFieldDefinition();

        $this->converter->toStorageFieldDefinition(new FieldDefinition(), $storageFieldDefinition);

        self::assertNull($storageFieldDefinition->dataText5);
    }

    public function testToFieldDefinition(): void
    {
        $storageFieldDefinition = new StorageFieldDefinition();
        $storageFieldDefinition->dataText5 = self::EXAMPLE_JSON;

        $this->serializer
            ->expects(self::once())
            ->method('decode')
            ->with(self::EXAMPLE_JSON)
            ->willReturn(self::EXAMPLE_DATA);

        $fieldDefinition = new FieldDefinition();

        $this->converter->toFieldDefinition($storageFieldDefinition, $fieldDefinition);

        self::assertEquals(
            new FieldSettings(self::EXAMPLE_DATA),
            $fieldDefinition->fieldTypeConstraints->fieldSettings
        );
    }

    public function testEmptyToFieldDefinition(): void
    {
        $this->serializer
            ->expects(self::never())
            ->method('decode');

        $fieldDefinition = new FieldDefinition();

        $this->converter->toFieldDefinition(new StorageFieldDefinition(), $fieldDefinition);

        self::assertNull($fieldDefinition->fieldTypeConstraints->fieldSettings);
    }
}
