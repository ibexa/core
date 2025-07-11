<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type\Gateway;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Type;
// For SORT_ORDER_* constants
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct as GroupUpdateStruct;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTest extends LanguageAwareTestCase
{
    /**
     * The DoctrineDatabase gateway to test.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\DoctrineDatabase
     */
    protected $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/languages.php');
    }

    public function testInsertGroup()
    {
        $gateway = $this->getGateway();

        $group = $this->getGroupFixture();

        $id = $gateway->insertGroup($group);

        $this->assertQueryResult(
            [
                [
                    'id' => '1',
                    'created' => '1032009743',
                    'creator_id' => '14',
                    'modified' => '1033922120',
                    'modifier_id' => '14',
                    'name' => 'Media',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select(
                    'id',
                    'created',
                    'creator_id',
                    'modified',
                    'modifier_id',
                    'name'
                )
                ->from(Gateway::CONTENT_TYPE_GROUP_TABLE)
        );
    }

    /**
     * Returns a Group fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Group
     */
    protected function getGroupFixture()
    {
        $group = new Group();

        $group->name = [
            'always-available' => 'eng-GB',
            'eng-GB' => 'Media',
        ];
        $group->description = [
            'always-available' => 'eng-GB',
            'eng-GB' => '',
        ];
        $group->identifier = 'Media';
        $group->created = 1032009743;
        $group->modified = 1033922120;
        $group->creatorId = 14;
        $group->modifierId = 14;

        return $group;
    }

    public function testUpdateGroup()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_groups.php'
        );

        $gateway = $this->getGateway();

        $struct = $this->getGroupUpdateStructFixture();

        $res = $gateway->updateGroup($struct);

        $this->assertQueryResult(
            [
                ['4'],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT(*)')
                ->from(Gateway::CONTENT_TYPE_GROUP_TABLE)
        );

        $q = $this->getDatabaseConnection()->createQueryBuilder();
        $q
            ->select(
                'id',
                'created',
                'creator_id',
                'modified',
                'modifier_id',
                'name'
            )
            ->from(Gateway::CONTENT_TYPE_GROUP_TABLE)
            ->orderBy('id');
        $this->assertQueryResult(
            [
                [
                    'id' => 1,
                    'created' => 1031216928,
                    'creator_id' => 14,
                    'modified' => 1033922106,
                    'modifier_id' => 14,
                    'name' => 'Content',
                ],
                [
                    'id' => 2,
                    'created' => 1031216941,
                    'creator_id' => 14,
                    'modified' => 1311454096,
                    'modifier_id' => 23,
                    'name' => 'UpdatedGroup',
                ],
                [
                    'id' => 3,
                    'created' => 1032009743,
                    'creator_id' => 14,
                    'modified' => 1033922120,
                    'modifier_id' => 14,
                    'name' => 'Media',
                ],
                [
                    'id' => 4,
                    'created' => 1634895910,
                    'creator_id' => 14,
                    'modified' => 1634895910,
                    'modifier_id' => 14,
                    'name' => 'System',
                ],
            ],
            $q
        );
    }

    /**
     * Returns a Group update struct fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct
     */
    protected function getGroupUpdateStructFixture()
    {
        $struct = new GroupUpdateStruct();

        $struct->id = 2;
        $struct->name = [
            'always-available' => 'eng-GB',
            'eng-GB' => 'UpdatedGroupName',
        ];
        $struct->description = [
            'always-available' => 'eng-GB',
            'eng-GB' => '',
        ];
        $struct->identifier = 'UpdatedGroup';
        $struct->modified = 1311454096;
        $struct->modifierId = 23;

        return $struct;
    }

    public function testCountTypesInGroup()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        self::assertEquals(
            3,
            $gateway->countTypesInGroup(1)
        );
        self::assertEquals(
            0,
            $gateway->countTypesInGroup(23)
        );
    }

    public function testCountGroupsForType()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        self::assertEquals(
            1,
            $gateway->countGroupsForType(1, 1)
        );
        self::assertEquals(
            0,
            $gateway->countGroupsForType(23, 0)
        );
    }

    public function testDeleteGroup()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_groups.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteGroup(2);

        $this->assertQueryResult(
            [
                ['1'],
                ['3'],
                ['4'],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('id')
                ->from(Gateway::CONTENT_TYPE_GROUP_TABLE)
        );
    }

    public function testLoadGroupData()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_groups.php'
        );

        $gateway = $this->getGateway();
        $data = $gateway->loadGroupData([2]);

        self::assertEquals(
            [
                [
                    'created' => '1031216941',
                    'creator_id' => '14',
                    'id' => '2',
                    'modified' => '1033922113',
                    'modifier_id' => '14',
                    'name' => 'Users',
                    'is_system' => '0',
                ],
            ],
            $data
        );
    }

    public function testLoadGroupDataByIdentifier()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_groups.php'
        );

        $gateway = $this->getGateway();
        $data = $gateway->loadGroupDataByIdentifier('Users');

        self::assertEquals(
            [
                [
                    'created' => '1031216941',
                    'creator_id' => '14',
                    'id' => '2',
                    'modified' => '1033922113',
                    'modifier_id' => '14',
                    'name' => 'Users',
                    'is_system' => '0',
                ],
            ],
            $data
        );
    }

    public function testLoadAllGroupsData()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_groups.php'
        );

        $gateway = $this->getGateway();
        $data = $gateway->loadAllGroupsData();

        self::assertCount(
            3,
            $data
        );

        self::assertEquals(
            [
                'created' => '1031216941',
                'creator_id' => '14',
                'id' => '2',
                'modified' => '1033922113',
                'modifier_id' => '14',
                'name' => 'Users',
                'is_system' => '0',
            ],
            $data[1]
        );
    }

    public function testLoadTypesDataForGroup()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();
        $rows = $gateway->loadTypesDataForGroup(1, 0);

        self::assertCount(
            4,
            $rows
        );
    }

    public function testLoadTypeData()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();
        $rows = $gateway->loadTypeData(1, 0);

        self::assertCount(
            3,
            $rows
        );
        self::assertCount(
            50,
            $rows[0]
        );

        /*
         * Store mapper fixture
         *
        file_put_contents(
            dirname( __DIR__ ) . '/_fixtures/map_load_type.php',
            "<?php\n\nreturn " . var_export( $rows, true ) . ";\n"
        );
         */
    }

    public function testLoadTypeDataByIdentifier()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();
        $rows = $gateway->loadTypeDataByIdentifier('folder', 0);

        self::assertCount(
            3,
            $rows
        );
        self::assertCount(
            50,
            $rows[0]
        );
    }

    public function testLoadTypeDataByRemoteId()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();
        $rows = $gateway->loadTypeDataByRemoteId('a3d405b81be900468eb153d774f4f0d2', 0);

        self::assertCount(
            3,
            $rows
        );
        self::assertCount(
            50,
            $rows[0]
        );
    }

    /**
     * Returns the expected data from creating a type.
     *
     * @return string[][]
     */
    public static function getTypeCreationExpectations()
    {
        return [
            ['always_available', 0],
            ['contentobject_name', '<short_name|name>'],
            ['created', '1024392098'],
            ['creator_id', '14'],
            ['identifier', 'folder'],
            ['initial_language_id', '2'],
            ['is_container', '1'],
            ['language_mask', 7],
            ['modified', '1082454875'],
            ['modifier_id', '14'],
            ['remote_id', 'a3d405b81be900468eb153d774f4f0d2'],
            ['serialized_description_list', 'a:2:{i:0;s:0:"";s:16:"always-available";b:0;}'],
            ['serialized_name_list', 'a:3:{s:16:"always-available";s:6:"eng-US";s:6:"eng-US";s:6:"Folder";s:6:"eng-GB";s:11:"Folder (GB)";}'],
            ['sort_field', 7],
            ['sort_order', 1],
            ['url_alias_name', ''],
            ['status', '0'],
        ];
    }

    /**
     * @dataProvider getTypeCreationExpectations
     */
    public function testInsertType($column, $expectation)
    {
        $gateway = $this->getGateway();
        $type = $this->getTypeFixture();

        $gateway->insertType($type);

        $this->assertQueryResult(
            [[$expectation]],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select($column)
                ->from(Gateway::CONTENT_TYPE_TABLE),
            'Inserted Type data incorrect in column ' . $column
        );
    }

    /**
     * Returns the data expected to be inserted in ibexa_content_type_name.
     *
     * @return string[][]
     */
    public static function getTypeCreationContentClassNameExpectations()
    {
        return [
            ['content_type_status', [0, 0]],
            ['language_id', [3, 4]],
            ['language_locale', ['eng-US', 'eng-GB']],
            ['name', ['Folder', 'Folder (GB)']],
        ];
    }

    /**
     * @dataProvider getTypeCreationContentClassNameExpectations
     */
    public function testInsertTypeContentClassName($column, $expectation)
    {
        $gateway = $this->getGateway();
        $type = $this->getTypeFixture();

        $gateway->insertType($type);

        $this->assertQueryResult(
            array_map(
                static function ($value) {
                    return [$value];
                },
                $expectation
            ),
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select($column)
                ->from(Gateway::CONTENT_TYPE_NAME_TABLE),
            'Inserted Type data incorrect in column ' . $column
        );
    }

    /**
     * Returns a Type fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    protected function getTypeFixture()
    {
        $type = new Type();

        $type->status = 0;
        $type->name = [
            'always-available' => 'eng-US',
            'eng-US' => 'Folder',
            'eng-GB' => 'Folder (GB)',
        ];
        $type->description = [
            0 => '',
            'always-available' => false,
        ];
        $type->identifier = 'folder';
        $type->created = 1024392098;
        $type->modified = 1082454875;
        $type->creatorId = 14;
        $type->modifierId = 14;
        $type->remoteId = 'a3d405b81be900468eb153d774f4f0d2';
        $type->urlAliasSchema = '';
        $type->nameSchema = '<short_name|name>';
        $type->isContainer = true;
        $type->initialLanguageId = 2;
        $type->sortField = Location::SORT_FIELD_CLASS_NAME;
        $type->sortOrder = Location::SORT_ORDER_ASC;
        $type->languageCodes = [
            'eng-US',
            'eng-GB',
        ];

        return $type;
    }

    public function testInsertFieldDefinition()
    {
        $gateway = $this->getGateway();

        $field = $this->getFieldDefinitionFixture();
        $storageField = $this->getStorageFieldDefinitionFixture();

        $gateway->insertFieldDefinition(23, 1, $field, $storageField);

        $this->assertQueryResult(
            [
                [
                    'content_type_id' => '23',
                    'serialized_name_list' => 'a:2:{s:16:"always-available";s:6:"eng-US";s:6:"eng-US";s:11:"Description";}',
                    'serialized_description_list' => 'a:2:{s:16:"always-available";s:6:"eng-GB";s:6:"eng-GB";s:16:"Some description";}',
                    'identifier' => 'description',
                    'category' => 'meta',
                    'placement' => '4',
                    'data_type_string' => 'ibexa_string',
                    'can_translate' => '1',
                    'is_required' => '1',
                    'is_information_collector' => '1',
                    'status' => '1',

                    'data_float1' => '0.1',
                    'data_float2' => '0.2',
                    'data_float3' => '0.3',
                    'data_float4' => '0.4',
                    'data_int1' => '1',
                    'data_int2' => '2',
                    'data_int3' => '3',
                    'data_int4' => '4',
                    'data_text1' => 'a',
                    'data_text2' => 'b',
                    'data_text3' => 'c',
                    'data_text4' => 'd',
                    'data_text5' => 'e',
                    'serialized_data_text' => 'a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select(
                    'content_type_id',
                    'serialized_name_list',
                    'serialized_description_list',
                    'identifier',
                    'category',
                    'placement',
                    'data_type_string',
                    'can_translate',
                    'is_required',
                    'is_information_collector',
                    'status',
                    'data_float1',
                    'data_float2',
                    'data_float3',
                    'data_float4',
                    'data_int1',
                    'data_int2',
                    'data_int3',
                    'data_int4',
                    'data_text1',
                    'data_text2',
                    'data_text3',
                    'data_text4',
                    'data_text5',
                    'serialized_data_text'
                )
                ->from(Gateway::FIELD_DEFINITION_TABLE),
            'FieldDefinition not inserted correctly'
        );
    }

    /**
     * Returns a FieldDefinition fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition
     */
    protected function getFieldDefinitionFixture()
    {
        $field = new FieldDefinition();

        $field->name = [
            'always-available' => 'eng-US',
            'eng-US' => 'Description',
        ];
        $field->description = [
            'always-available' => 'eng-GB',
            'eng-GB' => 'Some description',
        ];
        $field->identifier = 'description';
        $field->fieldGroup = 'meta';
        $field->position = 4;
        $field->fieldType = 'ibexa_string';
        $field->isTranslatable = true;
        $field->isRequired = true;
        $field->isInfoCollector = true;
        // $field->fieldTypeConstraints ???
        $field->defaultValue = [
            0 => '',
            'always-available' => false,
        ];

        return $field;
    }

    /**
     * Returns a StorageFieldDefinition fixture.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition
     */
    protected function getStorageFieldDefinitionFixture()
    {
        $fieldDef = new StorageFieldDefinition();

        $fieldDef->dataFloat1 = 0.1;
        $fieldDef->dataFloat2 = 0.2;
        $fieldDef->dataFloat3 = 0.3;
        $fieldDef->dataFloat4 = 0.4;

        $fieldDef->dataInt1 = 1;
        $fieldDef->dataInt2 = 2;
        $fieldDef->dataInt3 = 3;
        $fieldDef->dataInt4 = 4;

        $fieldDef->dataText1 = 'a';
        $fieldDef->dataText2 = 'b';
        $fieldDef->dataText3 = 'c';
        $fieldDef->dataText4 = 'd';
        $fieldDef->dataText5 = 'e';

        $fieldDef->serializedDataText = [
            'foo', 'bar',
        ];

        return $fieldDef;
    }

    public function testDeleteFieldDefinition()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteFieldDefinition(1, 0, 119);

        $this->assertQueryResult(
            [[5]],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT(*)')
                ->from(Gateway::FIELD_DEFINITION_TABLE)
        );
    }

    public function testUpdateFieldDefinition()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );
        $fieldDefinitionFixture = $this->getFieldDefinitionFixture();
        $fieldDefinitionFixture->id = 160;
        $storageFieldDefinitionFixture = $this->getStorageFieldDefinitionFixture();

        $gateway = $this->getGateway();
        $gateway->updateFieldDefinition(2, 0, $fieldDefinitionFixture, $storageFieldDefinitionFixture);

        $this->assertQueryResult(
            [
                // "random" sample
                [
                    'category' => 'meta',
                    'content_type_id' => '2',
                    'status' => '0',
                    'data_type_string' => 'ibexa_string',
                    'identifier' => 'description',
                    'is_information_collector' => '1',
                    'placement' => '4',
                    'serialized_description_list' => 'a:2:{s:16:"always-available";s:6:"eng-GB";s:6:"eng-GB";s:16:"Some description";}',

                    'data_float1' => '0.1',
                    'data_float2' => '0.2',
                    'data_float3' => '0.3',
                    'data_float4' => '0.4',
                    'data_int1' => '1',
                    'data_int2' => '2',
                    'data_int3' => '3',
                    'data_int4' => '4',
                    'data_text1' => 'a',
                    'data_text2' => 'b',
                    'data_text3' => 'c',
                    'data_text4' => 'd',
                    'data_text5' => 'e',
                    'serialized_data_text' => 'a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select(
                    'category',
                    'content_type_id',
                    'status',
                    'data_type_string',
                    'identifier',
                    'is_information_collector',
                    'placement',
                    'serialized_description_list',
                    'data_float1',
                    'data_float2',
                    'data_float3',
                    'data_float4',
                    'data_int1',
                    'data_int2',
                    'data_int3',
                    'data_int4',
                    'data_text1',
                    'data_text2',
                    'data_text3',
                    'data_text4',
                    'data_text5',
                    'serialized_data_text'
                )
                ->from(Gateway::FIELD_DEFINITION_TABLE)
                ->where('id = 160'),
            'FieldDefinition not updated correctly'
        );
    }

    public function testInsertGroupAssignment()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_groups.php'
        );

        $gateway = $this->getGateway();

        $gateway->insertGroupAssignment(3, 42, 1);

        $this->assertQueryResult(
            [
                [
                    'content_type_id' => '42',
                    'content_type_status' => '1',
                    'group_id' => '3',
                    'group_name' => 'Media',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select(
                    'content_type_id',
                    'content_type_status',
                    'group_id',
                    'group_name'
                )->from(Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)
        );
    }

    public function testDeleteGroupAssignment()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteGroupAssignment(1, 1, 0);

        $this->assertQueryResult(
            [['1']],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select(
                    'COUNT(*)'
                )->from(Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)
                ->where('content_type_id = 1')
        );
    }

    /**
     * @dataProvider getTypeUpdateExpectations
     */
    public function testUpdateType($fieldName, $expectedValue)
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $type = $this->getUpdateTypeFixture();

        $gateway->updateType(1, 0, $type);

        $this->assertQueryResult(
            [
                [
                    $fieldName => $expectedValue,
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select(
                    $fieldName
                )->from(Gateway::CONTENT_TYPE_TABLE)
                ->where('id = 1 AND status = 0'),
            "Incorrect value stored for '{$fieldName}'."
        );
    }

    public function testUpdateTypeName()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $type = $this->getUpdateTypeFixture();

        $gateway->updateType(1, 0, $type);

        $this->assertQueryResult(
            [
                [
                    'content_type_id' => 1,
                    'content_type_status' => 0,
                    'language_id' => 3,
                    'language_locale' => 'eng-US',
                    'name' => 'New Folder',
                ],
                [
                    'content_type_id' => 1,
                    'content_type_status' => 0,
                    'language_id' => 4,
                    'language_locale' => 'eng-GB',
                    'name' => 'New Folder for you',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('*')
                ->from(Gateway::CONTENT_TYPE_NAME_TABLE)
                ->where('content_type_id = 1 AND content_type_status = 0')
        );
    }

    /**
     * Returns expected data after update.
     *
     * Data provider for {@link testUpdateType()}.
     *
     * @return string[][]
     */
    public static function getTypeUpdateExpectations()
    {
        return [
            ['serialized_name_list', 'a:3:{s:16:"always-available";s:6:"eng-US";s:6:"eng-US";s:10:"New Folder";s:6:"eng-GB";s:18:"New Folder for you";}'],
            ['serialized_description_list', 'a:2:{i:0;s:0:"";s:16:"always-available";b:0;}'],
            ['identifier', 'new_folder'],
            ['modified', '1311621548'],
            ['modifier_id', '42'],
            ['remote_id', 'foobar'],
            ['url_alias_name', 'some scheke'],
            ['contentobject_name', '<short_name>'],
            ['is_container', '0'],
            ['initial_language_id', '23'],
            ['sort_field', '3'],
            ['sort_order', '0'],
            ['always_available', '1'],
        ];
    }

    /**
     * Returns a {@see \Ibexa\Contracts\Core\Persistence\Content\Type} fixture for update operation.
     */
    protected function getUpdateTypeFixture(): Type
    {
        $type = new Type();

        $type->name = [
            'always-available' => 'eng-US',
            'eng-US' => 'New Folder',
            'eng-GB' => 'New Folder for you',
        ];
        $type->description = [
            0 => '',
            'always-available' => false,
        ];
        $type->identifier = 'new_folder';
        $type->modified = 1311621548;
        $type->modifierId = 42;
        $type->remoteId = 'foobar';
        $type->urlAliasSchema = 'some scheke';
        $type->nameSchema = '<short_name>';
        $type->isContainer = false;
        $type->initialLanguageId = 23;
        $type->sortField = 3;
        $type->sortOrder = Location::SORT_ORDER_DESC;
        $type->defaultAlwaysAvailable = true;

        return $type;
    }

    public function testCountInstancesOfTypeExist()
    {
        $this->insertDatabaseFixture(
            // Fixture for content objects
            __DIR__ . '/../../_fixtures/contentobjects.php'
        );

        $gateway = $this->getGateway();
        $res = $gateway->countInstancesOfType(3, 0);

        self::assertEquals(
            6,
            $res
        );
    }

    public function testCountInstancesOfTypeNotExist()
    {
        $this->insertDatabaseFixture(
            // Fixture for content objects
            __DIR__ . '/../../_fixtures/contentobjects.php'
        );

        $gateway = $this->getGateway();
        $res = $gateway->countInstancesOfType(23422342, 1);

        self::assertEquals(
            0,
            $res
        );
    }

    public function testDeleteFieldDefinitionsForTypeExisting()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteFieldDefinitionsForType(1, 0);

        $countAffectedAttr = $this->getDatabaseConnection()->createQueryBuilder();
        $countAffectedAttr
            ->select('COUNT(*)')
            ->from(Gateway::FIELD_DEFINITION_TABLE)
            ->where(
                $countAffectedAttr->expr()->eq(
                    'content_type_id',
                    1
                )
            );
        // 1 left with version 1
        $this->assertQueryResult(
            [[1]],
            $countAffectedAttr
        );

        $countNotAffectedAttr = $this->getDatabaseConnection()->createQueryBuilder();
        $countNotAffectedAttr->select('COUNT(*)')
            ->from(Gateway::FIELD_DEFINITION_TABLE);

        $this->assertQueryResult(
            [[2]],
            $countNotAffectedAttr
        );
    }

    public function testDeleteFieldDefinitionsForTypeNotExisting()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteFieldDefinitionsForType(23, 1);

        $countNotAffectedAttr = $this->getDatabaseConnection()->createQueryBuilder();
        $countNotAffectedAttr->select('COUNT(*)')
            ->from(Gateway::FIELD_DEFINITION_TABLE);

        $this->assertQueryResult(
            [[5]],
            $countNotAffectedAttr
        );
    }

    public function testDeleteGroupAssignmentsForTypeExisting()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteGroupAssignmentsForType(1, 0);

        $countAffectedAttr = $this->getDatabaseConnection()->createQueryBuilder();
        $countAffectedAttr->select('COUNT(*)')
            ->from(Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE);

        $this->assertQueryResult(
            [[2]],
            $countAffectedAttr
        );
    }

    public function testDeleteGroupAssignmentsForTypeNotExisting()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteType(23, 1);

        $countAffectedAttr = $this->getDatabaseConnection()->createQueryBuilder();
        $countAffectedAttr->select('COUNT(*)')
            ->from(Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE);

        $this->assertQueryResult(
            [[3]],
            $countAffectedAttr
        );
    }

    public function testDeleteTypeExisting()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteType(1, 0);

        $countAffectedAttr = $this->getDatabaseConnection()->createQueryBuilder();
        $countAffectedAttr->select('COUNT(*)')
            ->from(Gateway::CONTENT_TYPE_TABLE);

        $this->assertQueryResult(
            [[1]],
            $countAffectedAttr
        );
    }

    public function testDeleteTypeNotExisting()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/existing_types.php'
        );

        $gateway = $this->getGateway();

        $gateway->deleteType(23, 1);

        $countAffectedAttr = $this->getDatabaseConnection()->createQueryBuilder();
        $countAffectedAttr->select('COUNT(*)')
            ->from(Gateway::CONTENT_TYPE_TABLE);

        $this->assertQueryResult(
            [[2]],
            $countAffectedAttr
        );
    }

    public function testPublishTypeAndFields()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/_fixtures/type_to_publish.php'
        );

        $gateway = $this->getGateway();
        $gateway->publishTypeAndFields(1, 1, 0);

        $this->assertQueryResult(
            [[1]],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * )')
                ->from(Gateway::CONTENT_TYPE_TABLE)
                ->where('id = 1 AND status = 0')
        );

        $this->assertQueryResult(
            [[2]],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * )')
                ->from(Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)
                ->where('content_type_id = 1 AND content_type_status = 0')
        );

        $this->assertQueryResult(
            [[3]],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * )')
                ->from(Gateway::FIELD_DEFINITION_TABLE)
                ->where('content_type_id = 1 AND status = 0')
        );

        $this->assertQueryResult(
            [[1]],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * )')
                ->from(Gateway::CONTENT_TYPE_NAME_TABLE)
                ->where('content_type_id = 1 AND content_type_status = 0')
        );
    }

    /**
     * Return the DoctrineDatabase gateway to test.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getGateway(): DoctrineDatabase
    {
        if (!isset($this->gateway)) {
            $this->gateway = new DoctrineDatabase(
                $this->getDatabaseConnection(),
                $this->getSharedGateway(),
                $this->getLanguageMaskGenerator()
            );
        }

        return $this->gateway;
    }
}
