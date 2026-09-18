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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(KeywordConverter::class)]
class KeywordTest extends TestCase
{
    /** @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\KeywordConverter */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new KeywordConverter();
    }

    #[Group('fieldType')]
    #[Group('keyword')]
    public function testToStorageValue(): void
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

    #[Group('fieldType')]
    #[Group('keyword')]
    public function testToFieldValue(): void
    {
        $storageFieldValue = new StorageFieldValue();
        $fieldValue = new FieldValue();

        $this->converter->toFieldValue($storageFieldValue, $fieldValue);
        self::assertSame([], $fieldValue->data);
        self::assertEquals('', $fieldValue->sortKey);
    }

    #[Group('fieldType')]
    #[Group('keyword')]
    public function testToStorageFieldDefinition(): void
    {
        $this->converter->toStorageFieldDefinition(new PersistenceFieldDefinition(), new StorageFieldDefinition());
    }

    #[Group('fieldType')]
    #[Group('keyword')]
    public function testToFieldDefinition(): void
    {
        $this->converter->toFieldDefinition(new StorageFieldDefinition(), new PersistenceFieldDefinition());
    }
}
