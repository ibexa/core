<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition as PersistenceFieldDefinition;
use Ibexa\Core\FieldType\RelationList\Type;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationConverter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationConverter
 */
class RelationTest extends TestCase
{
    /** @var MockObject|RelationConverter */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new RelationConverter();
    }

    /**
     * @group fieldType
     * @group relationlist
     */
    public function testToStorageFieldDefinition()
    {
        $fieldDefinition = new PersistenceFieldDefinition(
            [
                'fieldTypeConstraints' => new FieldTypeConstraints(
                    [
                        'fieldSettings' => [
                            'selectionMethod' => Type::SELECTION_BROWSE,
                            'selectionRoot' => 12345,
                            'selectionContentTypes' => ['article', 'blog_post'],
                            'rootDefaultLocation' => true,
                        ],
                    ]
                ),
            ]
        );

        $expectedStorageFieldDefinition = new StorageFieldDefinition();
        $expectedStorageFieldDefinition->dataText5 = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><constraints><allowed-class contentclass-identifier="article"/><allowed-class contentclass-identifier="blog_post"/></constraints><selection_type value="0"/><root_default_location value="1"/><contentobject-placement node-id="12345"/></related-objects>

EOT;
        // For BC these are still set
        $expectedStorageFieldDefinition->dataInt1 = 0;
        $expectedStorageFieldDefinition->dataInt2 = 12345;

        $actualStorageFieldDefinition = new StorageFieldDefinition();

        $this->converter->toStorageFieldDefinition($fieldDefinition, $actualStorageFieldDefinition);

        $this->assertEquals(
            $expectedStorageFieldDefinition,
            $actualStorageFieldDefinition
        );
    }

    /**
     * @group fieldType
     * @group relation
     *
     * @dataProvider provideDataForToFieldDefinition
     *
     * @param array<string, mixed> $expectedFieldSettings
     */
    public function testToFieldDefinition(
        string $dataText5,
        array $expectedFieldSettings
    ): void {
        $storageFieldDefinition = new StorageFieldDefinition();
        $storageFieldDefinition->dataInt1 = Type::SELECTION_BROWSE;
        $storageFieldDefinition->dataInt2 = 0;
        $storageFieldDefinition->dataText5 = $dataText5;

        $actualFieldDefinition = new PersistenceFieldDefinition();

        $this->converter->toFieldDefinition($storageFieldDefinition, $actualFieldDefinition);

        self::assertEquals(
            $expectedFieldSettings,
            $actualFieldDefinition->fieldTypeConstraints->fieldSettings
        );
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function provideDataForToFieldDefinition(): iterable
    {
        yield 'with allowed classes' => [
            <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><constraints><allowed-class contentclass-identifier="forum"/><allowed-class contentclass-identifier="folder"/></constraints><selection_type value="1"/><root_default_location value="1"/><contentobject-placement node-id="54321"/></related-objects>
EOT,
            [
                'selectionMethod' => Type::SELECTION_DROPDOWN,
                'selectionRoot' => 54321,
                'selectionContentTypes' => ['forum', 'folder'],
                'rootDefaultLocation' => true,
            ],
        ];

        yield 'with empty constraints' => [
            <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><constraints/><selection_type value="1"/><contentobject-placement node-id="54321"/></related-objects>
EOT,
            [
                'selectionMethod' => Type::SELECTION_DROPDOWN,
                'selectionRoot' => 54321,
                'selectionContentTypes' => [],
            ],
        ];

        yield 'without constraints' => [
            <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><selection_type value="1"/><contentobject-placement node-id="54321"/></related-objects>
EOT,
            [
                'selectionMethod' => Type::SELECTION_DROPDOWN,
                'selectionRoot' => 54321,
                'selectionContentTypes' => [],
            ],
        ];
    }
}

class_alias(RelationTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\Content\FieldValue\Converter\RelationTest');
