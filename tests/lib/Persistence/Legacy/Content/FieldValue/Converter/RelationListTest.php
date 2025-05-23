<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition as PersistenceFieldDefinition;
use Ibexa\Core\FieldType\RelationList\Type;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationListConverter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationListConverter
 */
class RelationListTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationListConverter */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = $this
            ->getMockBuilder(RelationListConverter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRelationXmlHashFromDB'])
            ->getMock();
    }

    /**
     * @group fieldType
     * @group relationlist
     */
    public function testToStorageValue()
    {
        $destinationContentIds = [3, 2, 1];
        $fieldValue = new FieldValue();
        $fieldValue->sortKey = false;
        $fieldValue->data = ['destinationContentIds' => $destinationContentIds];

        $expectedStorageFieldValue = new StorageFieldValue();
        $expectedStorageFieldValue->dataText = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><relation-list><relation-item priority="1" contentobject-id="3" contentobject-version="33" node-id="35" parent-node-id="36" contentclass-id="34" contentclass-identifier="37" contentobject-remote-id="32"/><relation-item priority="2" contentobject-id="2" contentobject-version="23" node-id="25" parent-node-id="26" contentclass-id="24" contentclass-identifier="27" contentobject-remote-id="22"/><relation-item priority="3" contentobject-id="1" contentobject-version="13" node-id="15" parent-node-id="16" contentclass-id="14" contentclass-identifier="17" contentobject-remote-id="12"/></relation-list></related-objects>

EOT;

        $actualStorageFieldValue = new StorageFieldValue();

        $this->converter
            ->expects(self::once())
            ->method('getRelationXmlHashFromDB')
            ->with($destinationContentIds)
            ->will(
                self::returnValue(
                    [
                        '1' => [
                            [
                                'ibexa_content_remote_id' => '12',
                                'ibexa_content_current_version' => '13',
                                'ibexa_content_content_type_id' => '14',
                                'ibexa_content_tree_node_id' => '15',
                                'ibexa_content_tree_parent_node_id' => '16',
                                'ibexa_content_type_identifier' => '17',
                            ],
                        ],
                        '3' => [
                            [
                                'ibexa_content_remote_id' => '32',
                                'ibexa_content_current_version' => '33',
                                'ibexa_content_content_type_id' => '34',
                                'ibexa_content_tree_node_id' => '35',
                                'ibexa_content_tree_parent_node_id' => '36',
                                'ibexa_content_type_identifier' => '37',
                            ],
                        ],
                        '2' => [
                            [
                                'ibexa_content_remote_id' => '22',
                                'ibexa_content_current_version' => '23',
                                'ibexa_content_content_type_id' => '24',
                                'ibexa_content_tree_node_id' => '25',
                                'ibexa_content_tree_parent_node_id' => '26',
                                'ibexa_content_type_identifier' => '27',
                            ],
                        ],
                    ]
                )
            );

        $this->converter->toStorageValue($fieldValue, $actualStorageFieldValue);

        self::assertEquals(
            $expectedStorageFieldValue,
            $actualStorageFieldValue
        );
    }

    /**
     * @group fieldType
     * @group relationlist
     */
    public function testToStorageValueEmpty()
    {
        $destinationContentIds = [];
        $fieldValue = new FieldValue();
        $fieldValue->sortKey = false;
        $fieldValue->data = ['destinationContentIds' => $destinationContentIds];

        $expectedStorageFieldValue = new StorageFieldValue();
        $expectedStorageFieldValue->dataText = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><relation-list/></related-objects>

EOT;

        $actualStorageFieldValue = new StorageFieldValue();

        $this->converter
            ->expects(self::once())
            ->method('getRelationXmlHashFromDB')
            ->with($destinationContentIds)
            ->will(self::returnValue([]));

        $this->converter->toStorageValue($fieldValue, $actualStorageFieldValue);

        self::assertEquals(
            $expectedStorageFieldValue,
            $actualStorageFieldValue
        );
    }

    /**
     * @group fieldType
     * @group relationlist
     */
    public function testToFieldValue()
    {
        $storageFieldValue = new StorageFieldValue();
        $storageFieldValue->sortKeyString = '';
        $storageFieldValue->dataText = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><relation-list><relation-item priority="2" contentobject-id="2" contentobject-version="23" node-id="25" parent-node-id="26" contentclass-id="24" contentclass-identifier="27" contentobject-remote-id="22"/><relation-item priority="3" contentobject-id="1" contentobject-version="13" node-id="15" parent-node-id="16" contentclass-id="14" contentclass-identifier="17" contentobject-remote-id="12"/><relation-item priority="1" contentobject-id="3" contentobject-version="33" node-id="35" parent-node-id="36" contentclass-id="34" contentclass-identifier="37" contentobject-remote-id="32"/></relation-list></related-objects>

EOT;

        $expectedFieldValue = new FieldValue();
        $expectedFieldValue->data = ['destinationContentIds' => [3, 2, 1]];

        $actualFieldValue = new FieldValue();

        $this->converter->toFieldValue($storageFieldValue, $actualFieldValue);

        self::assertEquals(
            $expectedFieldValue,
            $actualFieldValue
        );
    }

    /**
     * @group fieldType
     * @group relationlist
     */
    public function testToFieldValueEmpty()
    {
        $storageFieldValue = new StorageFieldValue();
        $storageFieldValue->sortKeyString = '';
        $storageFieldValue->dataText = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><relation-list/></related-objects>

EOT;

        $expectedFieldValue = new FieldValue();
        $expectedFieldValue->data = ['destinationContentIds' => []];

        $actualFieldValue = new FieldValue();

        $this->converter->toFieldValue($storageFieldValue, $actualFieldValue);

        self::assertEquals(
            $expectedFieldValue,
            $actualFieldValue
        );
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
                            'selectionDefaultLocation' => 12345,
                            'selectionContentTypes' => ['article', 'blog_post'],
                            'rootDefaultLocation' => true,
                        ],
                        'validators' => [
                            'RelationListValueValidator' => [
                                'selectionLimit' => 5,
                            ],
                        ],
                    ]
                ),
            ]
        );

        $expectedStorageFieldDefinition = new StorageFieldDefinition();
        $expectedStorageFieldDefinition->dataText5 = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects><constraints><allowed-class contentclass-identifier="article"/><allowed-class contentclass-identifier="blog_post"/></constraints><type value="2"/><object_class value=""/><selection_type value="0"/><contentobject-placement node-id="12345"/><root_default_location value="1"/><selection_limit value="5"/></related-objects>

EOT;

        $actualStorageFieldDefinition = new StorageFieldDefinition();

        $this->converter->toStorageFieldDefinition($fieldDefinition, $actualStorageFieldDefinition);

        self::assertEquals(
            $expectedStorageFieldDefinition,
            $actualStorageFieldDefinition
        );
    }

    /**
     * @group fieldType
     * @group relationlist
     */
    public function testToFieldDefinitionMultiple()
    {
        $storageFieldDefinition = new StorageFieldDefinition();
        $storageFieldDefinition->dataText5 = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<related-objects>
    <constraints>
        <allowed-class contentclass-identifier="forum"/>
        <allowed-class contentclass-identifier="folder"/>
    </constraints><type value="2"/>
    <object_class value=""/>
    <selection_type value="1"/>
    <selection_limit value="1"/>
    <root_default_location value="1"/>
    <contentobject-placement node-id="54321"/>
</related-objects>

EOT;

        $expectedFieldDefinition = new PersistenceFieldDefinition(
            [
                'fieldTypeConstraints' => new FieldTypeConstraints(
                    [
                        'fieldSettings' => [
                            'selectionMethod' => Type::SELECTION_DROPDOWN,
                            'selectionDefaultLocation' => 54321,
                            'selectionContentTypes' => ['forum', 'folder'],
                            'rootDefaultLocation' => true,
                        ],
                        'validators' => [
                            'RelationListValueValidator' => [
                                'selectionLimit' => 1,
                            ],
                        ],
                    ]
                ),
                'defaultValue' => new FieldValue(
                    [
                        'data' => ['destinationContentIds' => []],
                    ]
                ),
            ]
        );

        $actualFieldDefinition = new PersistenceFieldDefinition();

        $this->converter->toFieldDefinition($storageFieldDefinition, $actualFieldDefinition);

        self::assertEquals(
            $expectedFieldDefinition,
            $actualFieldDefinition
        );
    }
}
