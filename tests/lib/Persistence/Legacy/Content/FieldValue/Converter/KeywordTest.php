<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition as PersistenceFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\KeywordConverter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\KeywordConverter::class)]
class KeywordTest extends TestCase
{
    /** @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\KeywordConverter */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new KeywordConverter();
    }

    #[\PHPUnit\Framework\Attributes\Group('fieldType')]
    #[\PHPUnit\Framework\Attributes\Group('keyword')]
    public function testToStorageValue()
    {
        $value = new FieldValue();
        $value->data = ['key1', 'key2'];
        $value->sortKey = false;
        $storageFieldValue = new StorageFieldValue();

        $this->converter->toStorageValue($value, $storageFieldValue);
        self::assertNull($storageFieldValue->dataText);
        self::assertNull($storageFieldValue->dataInt);
        self::assertNull($storageFieldValue->dataFloat);
        self::assertEquals(0, $storageFieldValue->sortKeyInt);
        self::assertEquals('', $storageFieldValue->sortKeyString);
    }

    #[\PHPUnit\Framework\Attributes\Group('fieldType')]
    #[\PHPUnit\Framework\Attributes\Group('keyword')]
    public function testToFieldValue()
    {
        $storageFieldValue = new StorageFieldValue();
        $fieldValue = new FieldValue();

        $this->converter->toFieldValue($storageFieldValue, $fieldValue);
        self::assertSame([], $fieldValue->data);
        self::assertEquals('', $fieldValue->sortKey);
    }

    #[\PHPUnit\Framework\Attributes\Group('fieldType')]
    #[\PHPUnit\Framework\Attributes\Group('keyword')]
    public function testToStorageFieldDefinition()
    {
        $this->converter->toStorageFieldDefinition(new PersistenceFieldDefinition(), new StorageFieldDefinition());
    }

    #[\PHPUnit\Framework\Attributes\Group('fieldType')]
    #[\PHPUnit\Framework\Attributes\Group('keyword')]
    public function testToFieldDefinition()
    {
        $this->converter->toFieldDefinition(new StorageFieldDefinition(), new PersistenceFieldDefinition());
    }
}
