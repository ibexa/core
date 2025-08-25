<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Gateway;

use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTest extends LanguageAwareTestCase
{
    /**
     * Database gateway to test.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase
     */
    protected $databaseGateway;

    /**
     * @todo Fix not available fields
     */
    public function testInsertContentObject()
    {
        $struct = $this->getCreateStructFixture();

        $gateway = $this->getDatabaseGateway();
        $gateway->insertContentObject($struct);

        $this->assertQueryResult(
            [
                [
                    'name' => 'Content name',
                    'content_type_id' => '23',
                    'section_id' => '42',
                    'owner_id' => '13',
                    'current_version' => '1',
                    'initial_language_id' => '2',
                    'remote_id' => 'some_remote_id',
                    'language_mask' => '3',
                    'modified' => '0',
                    'published' => '0',
                    'status' => ContentInfo::STATUS_DRAFT,
                ],
            ],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select(
                    'name',
                    'content_type_id',
                    'section_id',
                    'owner_id',
                    'current_version',
                    'initial_language_id',
                    'remote_id',
                    'language_mask',
                    'modified',
                    'published',
                    'status'
                )->from(Gateway::CONTENT_ITEM_TABLE)
        );
    }

    /**
     * Returns a Content fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\CreateStruct
     */
    protected function getCreateStructFixture()
    {
        $struct = new CreateStruct();

        $struct->typeId = 23;
        $struct->sectionId = 42;
        $struct->ownerId = 13;
        $struct->initialLanguageId = 2;
        $struct->remoteId = 'some_remote_id';
        $struct->alwaysAvailable = true;
        $struct->modified = 456;
        $struct->name = [
            'eng-US' => 'Content name',
        ];
        $struct->fields = [
            new Field(['languageCode' => 'eng-US']),
        ];
        $struct->locations = [];

        return $struct;
    }

    /**
     * Returns a Content fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getContentFixture()
    {
        $content = new Content();

        $content->versionInfo = new VersionInfo();
        $content->versionInfo->names = [
            'eng-US' => 'Content name',
        ];
        $content->versionInfo->status = VersionInfo::STATUS_PENDING;

        $content->versionInfo->contentInfo = new ContentInfo();
        $content->versionInfo->contentInfo->contentTypeId = 23;
        $content->versionInfo->contentInfo->sectionId = 42;
        $content->versionInfo->contentInfo->ownerId = 13;
        $content->versionInfo->contentInfo->currentVersionNo = 2;
        $content->versionInfo->contentInfo->mainLanguageCode = 'eng-US';
        $content->versionInfo->contentInfo->remoteId = 'some_remote_id';
        $content->versionInfo->contentInfo->alwaysAvailable = true;
        $content->versionInfo->contentInfo->publicationDate = 123;
        $content->versionInfo->contentInfo->modificationDate = 456;
        $content->versionInfo->contentInfo->name = 'Content name';

        return $content;
    }

    /**
     * Returns a Version fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo
     */
    protected function getVersionFixture()
    {
        $version = new VersionInfo();

        $version->id = null;
        $version->versionNo = 1;
        $version->creatorId = 13;
        $version->status = 0;
        $version->creationDate = 1312278322;
        $version->modificationDate = 1312278323;
        $version->initialLanguageCode = self::ENG_GB;
        $version->contentInfo = new ContentInfo(
            [
                'id' => 2342,
                'alwaysAvailable' => true,
            ]
        );

        return $version;
    }

    public function testInsertVersion()
    {
        $version = $this->getVersionFixture();

        $gateway = $this->getDatabaseGateway();
        $gateway->insertVersion($version, []);

        $this->assertQueryResult(
            [
                [
                    'contentobject_id' => '2342',
                    'created' => '1312278322',
                    'creator_id' => '13',
                    'modified' => '1312278323',
                    'status' => '0',
                    'workflow_event_pos' => '0',
                    'version' => '1',
                    'language_mask' => '5',
                    'initial_language_id' => '4',
                    // Not needed, according to field mapping document
                    // 'user_id',
                ],
            ],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select(
                    'contentobject_id',
                    'created',
                    'creator_id',
                    'modified',
                    'status',
                    'workflow_event_pos',
                    'version',
                    'language_mask',
                    'initial_language_id'
                )->from(Gateway::CONTENT_VERSION_TABLE)
        );
    }

    public function testSetStatus()
    {
        $gateway = $this->getDatabaseGateway();

        // insert content
        $struct = $this->getCreateStructFixture();
        $contentId = $gateway->insertContentObject($struct);

        // insert version
        $version = $this->getVersionFixture();
        $version->contentInfo->id = $contentId;
        $gateway->insertVersion($version, []);

        self::assertTrue(
            $gateway->setStatus($version->contentInfo->id, $version->versionNo, VersionInfo::STATUS_PENDING)
        );

        $this->assertQueryResult(
            [[VersionInfo::STATUS_PENDING]],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select('status')
                ->from(Gateway::CONTENT_VERSION_TABLE)
        );

        // check that content status has not been set to published
        $this->assertQueryResult(
            [[VersionInfo::STATUS_DRAFT]],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select('status')
                ->from(Gateway::CONTENT_ITEM_TABLE)
        );
    }

    public function testSetStatusPublished()
    {
        $gateway = $this->getDatabaseGateway();

        // insert content
        $struct = $this->getCreateStructFixture();
        $contentId = $gateway->insertContentObject($struct);

        // insert version
        $version = $this->getVersionFixture();
        $version->contentInfo->id = $contentId;
        $gateway->insertVersion($version, []);

        self::assertTrue(
            $gateway->setStatus($version->contentInfo->id, $version->versionNo, VersionInfo::STATUS_PUBLISHED)
        );

        $this->assertQueryResult(
            [[VersionInfo::STATUS_PUBLISHED]],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select('status')
                ->from(Gateway::CONTENT_VERSION_TABLE)
        );

        // check that content status has been set to published
        $this->assertQueryResult(
            [[ContentInfo::STATUS_PUBLISHED]],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select('status')
                ->from(Gateway::CONTENT_ITEM_TABLE)
        );
    }

    public function testSetStatusUnknownVersion()
    {
        $gateway = $this->getDatabaseGateway();

        self::assertFalse(
            $gateway->setStatus(23, 42, 2)
        );
    }

    public function testUpdateContent()
    {
        $gateway = $this->getDatabaseGateway();

        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $metadataStruct = $this->getMetadataUpdateStructFixture();

        $gateway->updateContent(10, $metadataStruct);

        $this->assertQueryResult(
            [
                [
                    'initial_language_id' => '3',
                    'modified' => '234567',
                    'owner_id' => '42',
                    'published' => '123456',
                    'remote_id' => 'ghjk1234567890ghjk1234567890',
                    'name' => 'Thoth',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select(
                    'initial_language_id',
                    'modified',
                    'owner_id',
                    'published',
                    'remote_id',
                    'name'
                )->from(Gateway::CONTENT_ITEM_TABLE)
                ->where('id = 10')
        );
    }

    /**
     * Returns an UpdateStruct fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\UpdateStruct
     */
    protected function getUpdateStructFixture()
    {
        $struct = new UpdateStruct();
        $struct->creatorId = 23;
        $struct->fields = [];
        $struct->modificationDate = 234567;
        $struct->initialLanguageId = 2;

        return $struct;
    }

    /**
     * Returns a MetadataUpdateStruct fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct
     */
    protected function getMetadataUpdateStructFixture()
    {
        $struct = new MetadataUpdateStruct();
        $struct->ownerId = 42;
        $struct->publicationDate = 123456;
        $struct->mainLanguageId = 3;
        $struct->modificationDate = 234567;
        $struct->remoteId = 'ghjk1234567890ghjk1234567890';
        $struct->name = 'Thoth';

        return $struct;
    }

    public function testUpdateVersion()
    {
        $gateway = $this->getDatabaseGateway();

        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway->updateVersion(10, 2, $this->getUpdateStructFixture());

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $expr = $query->expr();
        $this->assertQueryResult(
            [
                [
                    'creator_id' => '23',
                    'initial_language_id' => '2',
                    'modified' => '234567',
                ],
            ],
            $query
                ->select(
                    'creator_id',
                    'initial_language_id',
                    'modified'
                )
                ->from(Gateway::CONTENT_VERSION_TABLE)
                ->where(
                    $expr->and(
                        $expr->eq('contentobject_id', 10),
                        $expr->eq('version', 2)
                    )
                )
        );
    }

    public function testInsertNewField()
    {
        $content = $this->getContentFixture();
        $content->versionInfo->contentInfo->id = 2342;

        $field = $this->getFieldFixture();
        $value = $this->getStorageValueFixture();

        $gateway = $this->getDatabaseGateway();
        $gateway->insertNewField($content, $field, $value);

        $this->assertQueryResult(
            [
                [
                    'content_type_field_definition_id' => '231',
                    'contentobject_id' => '2342',
                    'data_float' => '24.42',
                    'data_int' => '42',
                    'data_text' => 'Test text',
                    'data_type_string' => 'ibexa_string',
                    'language_code' => self::ENG_GB,
                    'language_id' => '4',
                    'sort_key_int' => '23',
                    'sort_key_string' => 'Test',
                    'version' => '1',
                ],
            ],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select(
                    'content_type_field_definition_id',
                    'contentobject_id',
                    'data_float',
                    'data_int',
                    'data_text',
                    'data_type_string',
                    'language_code',
                    'language_id',
                    'sort_key_int',
                    'sort_key_string',
                    'version'
                )->from(Gateway::CONTENT_FIELD_TABLE)
        );
    }

    public function testInsertNewAlwaysAvailableField()
    {
        $content = $this->getContentFixture();
        $content->versionInfo->contentInfo->id = 2342;
        // Set main language to the one used in the field fixture
        $content->versionInfo->contentInfo->mainLanguageCode = self::ENG_GB;

        $field = $this->getFieldFixture();
        $value = $this->getStorageValueFixture();

        $gateway = $this->getDatabaseGateway();
        $gateway->insertNewField($content, $field, $value);

        $this->assertQueryResult(
            [
                [
                    'content_type_field_definition_id' => '231',
                    'contentobject_id' => '2342',
                    'data_float' => '24.42',
                    'data_int' => '42',
                    'data_text' => 'Test text',
                    'data_type_string' => 'ibexa_string',
                    'language_code' => self::ENG_GB,
                    'language_id' => '5',
                    'sort_key_int' => '23',
                    'sort_key_string' => 'Test',
                    'version' => '1',
                ],
            ],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select(
                    'content_type_field_definition_id',
                    'contentobject_id',
                    'data_float',
                    'data_int',
                    'data_text',
                    'data_type_string',
                    'language_code',
                    'language_id',
                    'sort_key_int',
                    'sort_key_string',
                    'version'
                )->from(Gateway::CONTENT_FIELD_TABLE)
        );
    }

    public function testUpdateField()
    {
        $content = $this->getContentFixture();
        $content->versionInfo->contentInfo->id = 2342;

        $field = $this->getFieldFixture();
        $value = $this->getStorageValueFixture();

        $gateway = $this->getDatabaseGateway();
        $field->id = $gateway->insertNewField($content, $field, $value);

        $newValue = new StorageFieldValue(
            [
                'dataFloat' => 124.42,
                'dataInt' => 142,
                'dataText' => 'New text',
                'sortKeyInt' => 123,
                'sortKeyString' => 'new_text',
            ]
        );

        $gateway->updateField($field, $newValue);

        $this->assertQueryResult(
            [
                [
                    'data_float' => '124.42',
                    'data_int' => '142',
                    'data_text' => 'New text',
                    'sort_key_int' => '123',
                    'sort_key_string' => 'new_text',
                ],
            ],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select(
                    'data_float',
                    'data_int',
                    'data_text',
                    'sort_key_int',
                    'sort_key_string'
                )->from(Gateway::CONTENT_FIELD_TABLE)
        );
    }

    public function testUpdateNonTranslatableField()
    {
        $content = $this->getContentFixture();
        $content->versionInfo->contentInfo->id = 2342;

        $fieldGb = $this->getFieldFixture();
        $fieldUs = $this->getOtherLanguageFieldFixture();
        $value = $this->getStorageValueFixture();

        $gateway = $this->getDatabaseGateway();
        $fieldGb->id = $gateway->insertNewField($content, $fieldGb, $value);
        $fieldUs->id = $gateway->insertNewField($content, $fieldUs, $value);

        $updateStruct = new Content\UpdateStruct();

        $newValue = new StorageFieldValue(
            [
                'dataFloat' => 124.42,
                'dataInt' => 142,
                'dataText' => 'New text',
                'sortKeyInt' => 123,
                'sortKeyString' => 'new_text',
            ]
        );

        $gateway->updateNonTranslatableField($fieldGb, $newValue, $content->versionInfo->contentInfo->id);

        $this->assertQueryResult(
            [
                // Both fields updated
                [
                    'data_float' => '124.42',
                    'data_int' => '142',
                    'data_text' => 'New text',
                    'sort_key_int' => '123',
                    'sort_key_string' => 'new_text',
                ],
                [
                    'data_float' => '124.42',
                    'data_int' => '142',
                    'data_text' => 'New text',
                    'sort_key_int' => '123',
                    'sort_key_string' => 'new_text',
                ],
            ],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select(
                    'data_float',
                    'data_int',
                    'data_text',
                    'sort_key_int',
                    'sort_key_string'
                )->from(Gateway::CONTENT_FIELD_TABLE)
        );
    }

    public function testListVersions(): void
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();
        $res = $gateway->listVersions(226);

        self::assertCount(
            2,
            $res
        );

        foreach ($res as $row) {
            self::assertCount(
                23,
                $row
            );
        }

        self::assertEquals(
            675,
            $res[0]['content_version_id']
        );
        self::assertEquals(
            676,
            $res[1]['content_version_id']
        );
    }

    public function testListVersionNumbers()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();
        $res = $gateway->listVersionNumbers(226);

        self::assertEquals([1, 2], $res);
    }

    public function testListVersionsForUser()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();
        $res = $gateway->listVersionsForUser(14);

        self::assertCount(
            2,
            $res
        );

        foreach ($res as $row) {
            self::assertCount(
                23,
                $row
            );
        }

        self::assertEquals(
            677,
            $res[0]['content_version_id']
        );
        self::assertEquals(
            0,
            $res[0]['content_version_status']
        );
        self::assertEquals(
            678,
            $res[1]['content_version_id']
        );
        self::assertEquals(
            0,
            $res[1]['content_version_status']
        );
    }

    public function testLoadWithAllTranslations()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();
        $res = $gateway->load(226, 2);

        $this->assertValuesInRows(
            'content_field_language_code',
            ['eng-US', self::ENG_GB],
            $res
        );

        $this->assertValuesInRows(
            'content_field_language_id',
            ['2', '4'],
            $res
        );
    }

    public function testCreateFixtureForMapperExtractContentFromRowsMultipleVersions()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        $resFirst = $gateway->load(11, 1);
        $resSecond = $gateway->load(11, 2);

        $res = array_merge($resFirst, $resSecond);

        $orig = include __DIR__ . '/../_fixtures/extract_content_from_rows_multiple_versions.php';

        /*$this->storeFixture(
            __DIR__ . '/../_fixtures/extract_content_from_rows_multiple_versions.php',
            $res
        );*/

        self::assertEquals($orig, $res, 'Fixtures differ between what was previously stored(expected) and what it now generates(actual), this hints either some mistake in impl or that the fixture (../_fixtures/extract_content_from_rows_multiple_versions.php) and tests needs to be adapted.');
    }

    public function testCreateFixtureForMapperExtractContentFromRows()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        $res = array_merge($gateway->load(226, 2));

        $orig = include __DIR__ . '/../_fixtures/extract_content_from_rows.php';

        /*$this->storeFixture(
            __DIR__ . '/../_fixtures/extract_content_from_rows.php',
            $res
        );*/

        self::assertEquals($orig, $res, 'Fixtures differ between what was previously stored(expected) and what it now generates(actual), this hints either some mistake in impl or that the fixture (../_fixtures/extract_content_from_rows.php) and tests needs to be adapted.');
    }

    public function testLoadWithSingleTranslation()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();
        $res = $gateway->load(226, 2, [self::ENG_GB]);

        $this->assertValuesInRows(
            'content_field_language_code',
            [self::ENG_GB],
            $res
        );
        $this->assertValuesInRows(
            'content_field_language_id',
            ['4'],
            $res
        );
        self::assertCount(
            1,
            $res
        );
    }

    public function testLoadNonExistentTranslation()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();
        $res = $gateway->load(226, 2, ['de-DE']);

        self::assertCount(
            0,
            $res
        );
    }

    /**
     * Asserts that $columnKey in $actualRows exactly contains $expectedValues.
     *
     * @param string $columnKey
     * @param string[] $expectedValues
     * @param string[][] $actualRows
     */
    protected function assertValuesInRows($columnKey, array $expectedValues, array $actualRows)
    {
        $expectedValues = array_fill_keys(
            array_values($expectedValues),
            true
        );

        $containedValues = [];

        foreach ($actualRows as $row) {
            if (isset($row[$columnKey])) {
                $containedValues[$row[$columnKey]] = true;
            }
        }

        self::assertEquals(
            $expectedValues,
            $containedValues
        );
    }

    public function testGetAllLocationIds()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        self::assertEquals(
            [228],
            $gateway->getAllLocationIds(226)
        );
    }

    public function testGetFieldIdsByType()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        self::assertEquals(
            [
                'ibexa_string' => [841],
                'ibexa_image' => [843],
                'ibexa_keyword' => [844],
            ],
            $gateway->getFieldIdsByType(149)
        );
    }

    public function testGetFieldIdsByTypeWithSecondArgument()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        self::assertEquals(
            [
                'ibexa_string' => [4001, 4002],
            ],
            $gateway->getFieldIdsByType(225, 2)
        );
    }

    public function testDeleteRelationsTo()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentRelations(),
            'from' => $this->countContentRelations(149),
            'to' => $this->countContentRelations(null, 149),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteRelations(149);

        self::assertEquals(
            // yes, relates to itself!
            [
                'all' => $beforeCount['all'] - 2,
                'from' => $beforeCount['from'] - 1,
                'to' => $beforeCount['to'] - 2,
            ],
            [
                'all' => $this->countContentRelations(),
                'from' => $this->countContentRelations(149),
                'to' => $this->countContentRelations(null, 149),
            ]
        );
    }

    public function testDeleteRelationsFrom()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentRelations(),
            'from' => $this->countContentRelations(75),
            'to' => $this->countContentRelations(null, 75),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteRelations(75);

        self::assertEquals(
            [
                'all' => $beforeCount['all'] - 6,
                'from' => $beforeCount['from'] - 6,
                'to' => $beforeCount['to'],
            ],
            [
                'all' => $this->countContentRelations(),
                'from' => $this->countContentRelations(75),
                'to' => $this->countContentRelations(null, 75),
            ]
        );
    }

    public function testDeleteRelationsWithSecondArgument()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentRelations(),
            'from' => $this->countContentRelations(225),
            'to' => $this->countContentRelations(null, 225),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteRelations(225, 2);

        self::assertEquals(
            [
                'all' => $beforeCount['all'] - 1,
                'from' => $beforeCount['from'] - 1,
                'to' => $beforeCount['to'],
            ],
            [
                'all' => $this->countContentRelations(),
                'from' => $this->countContentRelations(225),
                'to' => $this->countContentRelations(null, 225),
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testDeleteField(): void
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = $this->countContentFields();

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteField(22);

        self::assertEquals(
            $beforeCount - 2,
            $this->countContentFields()
        );

        $this->assertQueryResult(
            [],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('*')
                ->from(Gateway::CONTENT_FIELD_TABLE)
                ->where('id=22')
        );
    }

    public function testDeleteFields()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentFields(),
            'this' => $this->countContentFields(4),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteFields(4);

        self::assertEquals(
            [
                'all' => $beforeCount['all'] - 2,
                'this' => 0,
            ],
            [
                'all' => $this->countContentFields(),
                'this' => $this->countContentFields(4),
            ]
        );
    }

    public function testDeleteFieldsWithSecondArgument()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentFields(),
            'this' => $this->countContentFields(225),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteFields(225, 2);

        self::assertEquals(
            [
                'all' => $beforeCount['all'] - 2,
                'this' => $beforeCount['this'] - 2,
            ],
            [
                'all' => $this->countContentFields(),
                'this' => $this->countContentFields(225),
            ]
        );
    }

    public function testDeleteVersions()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentVersions(),
            'this' => $this->countContentVersions(14),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteVersions(14);

        self::assertEquals(
            [
                'all' => $beforeCount['all'] - 2,
                'this' => 0,
            ],
            [
                'all' => $this->countContentVersions(),
                'this' => $this->countContentVersions(14),
            ]
        );
    }

    public function testDeleteVersionsWithSecondArgument()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentVersions(),
            'this' => $this->countContentVersions(225),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteVersions(225, 2);

        self::assertEquals(
            [
                'all' => $beforeCount['all'] - 1,
                'this' => $beforeCount['this'] - 1,
            ],
            [
                'all' => $this->countContentVersions(),
                'this' => $this->countContentVersions(225),
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testSetName()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        $gateway->setName(14, 2, 'Hello world!', self::ENG_GB);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [[self::ENG_GB, 2, 14, 4, 'Hello world!', self::ENG_GB]],
            $query
                ->select(
                    'content_translation',
                    'content_version',
                    'contentobject_id',
                    'language_id',
                    'name',
                    'real_translation'
                )
                ->from(Gateway::CONTENT_NAME_TABLE)
                ->where('contentobject_id = :content_id')
                ->andWhere('content_version = :version_no')
                ->andWhere('content_translation = :language_code')
                ->setParameter('content_id', 14, ParameterType::INTEGER)
                ->setParameter('version_no', 2, ParameterType::INTEGER)
                ->setParameter('language_code', self::ENG_GB, ParameterType::STRING)
        );
    }

    public function testDeleteNames()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentNames(),
            'this' => $this->countContentNames(14),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteNames(14);

        self::assertEquals(
            [
                'all' => $beforeCount['all'] - 2,
                'this' => 0,
            ],
            [
                'all' => $this->countContentNames(),
                'this' => $this->countContentNames(14),
            ]
        );
    }

    public function testDeleteNamesWithSecondArgument()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = [
            'all' => $this->countContentNames(),
            'this' => $this->countContentNames(225),
        ];

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteNames(225, 2);

        self::assertEquals(
            [
                'all' => $beforeCount['all'] - 1,
                'this' => $beforeCount['this'] - 1,
            ],
            [
                'all' => $this->countContentNames(),
                'this' => $this->countContentNames(225),
            ]
        );
    }

    public function testDeleteContent()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $beforeCount = $this->countContent();

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteContent(14);

        self::assertEquals(
            [
                'all' => $beforeCount - 1,
                'this' => 0,
            ],
            [
                'all' => $this->countContent(),
                'this' => $this->countContent(14),
            ]
        );
    }

    public function testLoadRelationThrowNotFoundExceptionWhenRelationNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Could not find \'Relation\' with identifier \'3\'');

        $this->insertRelationFixture();
        $gateway = $this->getDatabaseGateway();
        $gateway->loadRelation(3);
    }

    public function testLoadRelationById(): void
    {
        $relationId = 2;

        $this->insertRelationFixture();

        $gateway = $this->getDatabaseGateway();

        $relation = $gateway->loadRelation($relationId);

        self::assertEquals(
            $relationId,
            $relation['content_link_id']
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadRelations(): void
    {
        $this->insertRelationFixture();

        $gateway = $this->getDatabaseGateway();

        $relations = $gateway->loadRelations(57);

        self::assertCount(3, $relations);

        $this->assertValuesInRows(
            'content_link_to_contentobject_id',
            [58, 59, 60],
            $relations
        );

        $this->assertValuesInRows(
            'content_link_from_contentobject_id',
            [57],
            $relations
        );
        $this->assertValuesInRows(
            'content_link_from_contentobject_version',
            [2],
            $relations
        );
    }

    public function testLoadRelationsByType()
    {
        $this->insertRelationFixture();

        $gateway = $this->getDatabaseGateway();

        $relations = $gateway->loadRelations(57, null, RelationType::COMMON->value);

        self::assertCount(1, $relations, 'Expecting one relation to be loaded');

        $this->assertValuesInRows(
            'content_link_relation_type',
            [RelationType::COMMON->value],
            $relations
        );

        $this->assertValuesInRows(
            'content_link_to_contentobject_id',
            [58],
            $relations
        );
    }

    public function testLoadRelationsByVersion()
    {
        $this->insertRelationFixture();

        $gateway = $this->getDatabaseGateway();

        $relations = $gateway->loadRelations(57, 1);

        self::assertCount(1, $relations, 'Expecting one relation to be loaded');

        $this->assertValuesInRows(
            'content_link_to_contentobject_id',
            [58],
            $relations
        );
    }

    public function testLoadRelationsNoResult()
    {
        $this->insertRelationFixture();

        $gateway = $this->getDatabaseGateway();

        $relations = $gateway->loadRelations(57, 1, RelationType::EMBED->value);

        self::assertCount(0, $relations, 'Expecting no relation to be loaded');
    }

    public function testLoadReverseRelations()
    {
        $this->insertRelationFixture();

        $gateway = $this->getDatabaseGateway();

        $relations = $gateway->loadReverseRelations(58);

        self::assertCount(2, $relations);

        $this->assertValuesInRows(
            'content_link_from_contentobject_id',
            [57, 61],
            $relations
        );
    }

    public function testLoadReverseRelationsWithType()
    {
        $this->insertRelationFixture();

        $gateway = $this->getDatabaseGateway();

        $relations = $gateway->loadReverseRelations(58, RelationType::COMMON->value);

        self::assertCount(1, $relations);

        $this->assertValuesInRows(
            'content_link_from_contentobject_id',
            [57],
            $relations
        );

        $this->assertValuesInRows(
            'content_link_relation_type',
            [RelationType::COMMON->value],
            $relations
        );
    }

    /**
     * Inserts the relation database fixture from relation_data.php.
     */
    protected function insertRelationFixture()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/relations_data.php'
        );
    }

    public function testGetLastVersionNumber()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        self::assertEquals(
            1,
            $gateway->getLastVersionNumber(4)
        );
    }

    public function testInsertRelation()
    {
        $struct = $this->getRelationCreateStructFixture();
        $gateway = $this->getDatabaseGateway();
        $gateway->insertRelation($struct);

        $this->assertQueryResult(
            [
                [
                    'id' => 1,
                    'from_contentobject_id' => $struct->sourceContentId,
                    'from_contentobject_version' => $struct->sourceContentVersionNo,
                    'content_type_field_definition_id' => $struct->sourceFieldDefinitionId,
                    'to_contentobject_id' => $struct->destinationContentId,
                    'relation_type' => $struct->type,
                ],
            ],
            $this->getDatabaseConnection()
                ->createQueryBuilder()
                ->select(
                    'id',
                    'from_contentobject_id',
                    'from_contentobject_version',
                    'content_type_field_definition_id',
                    'to_contentobject_id',
                    'relation_type'
                )
                ->from(Gateway::CONTENT_RELATION_TABLE)
                ->where('id = 1')
        );
    }

    public function testDeleteRelation()
    {
        $this->insertRelationFixture();

        self::assertEquals(4, $this->countContentRelations(57));

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteRelation(2, RelationType::COMMON->value);

        self::assertEquals(3, $this->countContentRelations(57));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testDeleteRelationWithCompositeBitmask(): void
    {
        $this->insertRelationFixture();

        $gateway = $this->getDatabaseGateway();
        $gateway->deleteRelation(11, RelationType::COMMON->value);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [['relation_type' => RelationType::LINK->value]],
            $query
                ->select('relation_type')
                ->from(Gateway::CONTENT_RELATION_TABLE)
                ->where(
                    $query->expr()->eq(
                        'id',
                        $query->createPositionalParameter(11, ParameterType::INTEGER)
                    )
                )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUpdateAlwaysAvailableFlagRemove(): void
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();
        $gateway->updateAlwaysAvailableFlag(103, false);

        $connection = $this->getDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $this->assertQueryResult(
            [['id' => 2]],
            $query
                ->select('language_mask')
                ->from(Gateway::CONTENT_ITEM_TABLE)
                ->where(
                    $query->expr()->eq(
                        'id',
                        $query->createPositionalParameter(103, ParameterType::INTEGER)
                    )
                )
        );

        $query = $connection->createQueryBuilder();
        $this->assertQueryResult(
            [['language_id' => 2]],
            $query
                ->select(
                    'language_id'
                )->from(
                    Gateway::CONTENT_NAME_TABLE
                )->where(
                    $query->expr()->and(
                        $query->expr()->eq(
                            'contentobject_id',
                            $query->createPositionalParameter(103, ParameterType::INTEGER)
                        ),
                        $query->expr()->eq(
                            'content_version',
                            $query->createPositionalParameter(1, ParameterType::INTEGER)
                        )
                    )
                )
        );

        $query = $connection->createQueryBuilder();
        $this->assertQueryResult(
            [
                ['language_id' => 2],
            ],
            $query
                ->select('DISTINCT language_id')
                ->from(Gateway::CONTENT_FIELD_TABLE)
                ->where(
                    $query->expr()->and(
                        $query->expr()->eq('contentobject_id', 103),
                        $query->expr()->eq('version', 1)
                    )
                )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUpdateAlwaysAvailableFlagAdd(): void
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();
        $contentId = 102;
        $gateway->updateAlwaysAvailableFlag($contentId, true);

        $connection = $this->getDatabaseConnection();
        $expectedLanguageId = 3;
        $this->assertQueryResult(
            [['id' => $expectedLanguageId]],
            $connection->createQueryBuilder()
                ->select('language_mask')
                ->from(Gateway::CONTENT_ITEM_TABLE)
                ->where('id = 102')
        );

        $versionNo = 1;
        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [
                ['language_id' => $expectedLanguageId],
            ],
            $query
                ->select('language_id')
                ->from(Gateway::CONTENT_NAME_TABLE)
                ->where(
                    $query->expr()->and(
                        $query->expr()->eq(
                            'contentobject_id',
                            $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                        ),
                        $query->expr()->eq(
                            'content_version',
                            $query->createPositionalParameter($versionNo, ParameterType::INTEGER)
                        )
                    )
                )
        );

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [
                ['language_id' => $expectedLanguageId],
            ],
            $query
                ->select('DISTINCT language_id')
                ->from(Gateway::CONTENT_FIELD_TABLE)
                ->where(
                    $query->expr()->and(
                        $query->expr()->eq(
                            'contentobject_id',
                            $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                        ),
                        $query->expr()->eq(
                            'version',
                            $query->createPositionalParameter($versionNo, ParameterType::INTEGER)
                        )
                    )
                )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testUpdateContentAddAlwaysAvailableFlagMultilingual(): void
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects_multilingual.php'
        );

        $gateway = $this->getDatabaseGateway();
        $contentMetadataUpdateStruct = new MetadataUpdateStruct(
            [
                'mainLanguageId' => 4,
                'alwaysAvailable' => true,
            ]
        );
        $gateway->updateContent(4, $contentMetadataUpdateStruct);

        $this->assertQueryResult(
            [['id' => 7]],
            $this->getDatabaseConnection()->createQueryBuilder()->select(
                'language_mask'
            )->from(
                Gateway::CONTENT_ITEM_TABLE
            )->where(
                'id = 4'
            )
        );

        $this->assertContentVersionAttributesLanguages(
            4,
            2,
            [
                ['id' => '7', 'language_id' => 2],
                ['id' => '8', 'language_id' => 5],
            ]
        );

        $this->assertContentVersionAttributesLanguages(
            4,
            1,
            [
                ['id' => '7', 'language_id' => 2],
                ['id' => '8', 'language_id' => 5],
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testUpdateContentRemoveAlwaysAvailableFlagMultilingual(): void
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects_multilingual.php'
        );

        $gateway = $this->getDatabaseGateway();
        $contentMetadataUpdateStruct = new MetadataUpdateStruct(
            [
                'mainLanguageId' => 4,
                'alwaysAvailable' => false,
            ]
        );
        $gateway->updateContent(4, $contentMetadataUpdateStruct);

        $this->assertQueryResult(
            [['id' => 6]],
            $this->getDatabaseConnection()->createQueryBuilder()->select(
                'language_mask'
            )->from(
                Gateway::CONTENT_ITEM_TABLE
            )->where(
                'id = 4'
            )
        );

        $this->assertContentVersionAttributesLanguages(
            4,
            2,
            [
                ['id' => '7', 'language_id' => 2],
                ['id' => '8', 'language_id' => 4],
            ]
        );

        $this->assertContentVersionAttributesLanguages(
            4,
            1,
            [
                ['id' => '7', 'language_id' => 2],
                ['id' => '8', 'language_id' => 5],
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadVersionInfo(): void
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        $resFirst = $gateway->loadVersionInfo(11, 1);
        $resSecond = $gateway->loadVersionInfo(11, 2);

        $res = array_merge($resFirst, $resSecond);

        $orig = include __DIR__ . '/../_fixtures/extract_version_info_from_rows_multiple_versions.php';

        self::assertEquals($orig, $res, 'Fixtures differ between what was previously stored(expected) and what it now generates(actual), this hints either some mistake in impl or that the fixture (../_fixtures/extract_content_from_rows_multiple_versions.php) and tests needs to be adapted.');
    }

    /**
     * Counts the number of relations in the database.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function countContentRelations(?int $fromId = null, ?int $toId = null): int
    {
        $connection = $this->getDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->select('COUNT(id)')
            ->from(Gateway::CONTENT_RELATION_TABLE);

        if ($fromId !== null) {
            $query->where(
                $query->expr()->eq(
                    'from_contentobject_id',
                    $query->createPositionalParameter($fromId)
                )
            );
        }
        if ($toId !== null) {
            $query->andWhere(
                $query->expr()->eq(
                    'to_contentobject_id',
                    $query->createPositionalParameter($toId)
                )
            );
        }

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Counts the number of fields.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function countContentFields(?int $contentId = null): int
    {
        $connection = $this->getDatabaseConnection();

        $query = $connection->createQueryBuilder();
        $query
            ->select('COUNT(id)')
            ->from(Gateway::CONTENT_FIELD_TABLE);

        if ($contentId !== null) {
            $query->where(
                $query->expr()->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );
        }

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Counts the number of versions.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function countContentVersions(?int $contentId = null): int
    {
        $connection = $this->getDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->select('COUNT(id)')
            ->from(Gateway::CONTENT_VERSION_TABLE);

        if ($contentId !== null) {
            $query->where(
                $query->expr()->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );
        }

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Counts the number of content names.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function countContentNames(?int $contentId = null): int
    {
        $connection = $this->getDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->select('COUNT(contentobject_id)')
            ->from(Gateway::CONTENT_NAME_TABLE);

        if ($contentId !== null) {
            $query->where(
                $query->expr()->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );
        }

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Counts the number of content objects.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function countContent(?int $contentId = null): int
    {
        $connection = $this->getDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->select('COUNT(id)')
            ->from(Gateway::CONTENT_ITEM_TABLE);

        if ($contentId !== null) {
            $query->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );
        }

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Stores $fixture in $file to be required as a fixture.
     *
     * @param string $file
     * @param mixed $fixture
     */
    protected function storeFixture($file, $fixture)
    {
        file_put_contents(
            $file,
            "<?php\n\nreturn " . str_replace(" \n", "\n", var_export($fixture, true)) . ";\n"
        );
    }

    /**
     * Returns a Field fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Field
     */
    protected function getFieldFixture()
    {
        $field = new Field();

        $field->fieldDefinitionId = 231;
        $field->type = 'ibexa_string';
        $field->languageCode = self::ENG_GB;
        $field->versionNo = 1;

        return $field;
    }

    /**
     * Returns a Field fixture in a different language.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Field
     */
    protected function getOtherLanguageFieldFixture()
    {
        $field = $this->getFieldFixture();
        $field->languageCode = 'eng-US';

        return $field;
    }

    /**
     * Returns a StorageFieldValue fixture.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue
     */
    protected function getStorageValueFixture()
    {
        $value = new StorageFieldValue();

        $value->dataFloat = 24.42;
        $value->dataInt = 42;
        $value->dataText = 'Test text';
        $value->sortKeyInt = 23;
        $value->sortKeyString = 'Test';

        return $value;
    }

    /**
     * Returns a ready to test DoctrineDatabase gateway.
     */
    protected function getDatabaseGateway(): DoctrineDatabase
    {
        if (!isset($this->databaseGateway)) {
            $connection = $this->getDatabaseConnection();
            $this->databaseGateway = new DoctrineDatabase(
                $connection,
                $this->getSharedGateway(),
                new DoctrineDatabase\QueryBuilder($connection),
                $this->getLanguageHandler(),
                $this->getLanguageMaskGenerator()
            );
        }

        return $this->databaseGateway;
    }

    /**
     * DoctrineDatabaseTest::getRelationCreateStructFixture().
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct
     */
    protected function getRelationCreateStructFixture()
    {
        $struct = new RelationCreateStruct();

        $struct->destinationContentId = 1;
        $struct->sourceContentId = 1;
        $struct->sourceContentVersionNo = 1;
        $struct->sourceFieldDefinitionId = 0;
        $struct->type = RelationType::COMMON->value;

        return $struct;
    }

    /**
     * @param int $contentId
     * @param int $versionNo
     * @param array $expectation
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function assertContentVersionAttributesLanguages(
        int $contentId,
        int $versionNo,
        array $expectation
    ): void {
        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            $expectation,
            $query
                ->select('DISTINCT id, language_id')
                ->from(Gateway::CONTENT_FIELD_TABLE)
                ->where(
                    $query->expr()->and(
                        $query->expr()->eq(
                            'contentobject_id',
                            $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                        ),
                        $query->expr()->eq(
                            'version',
                            $query->createPositionalParameter($versionNo, ParameterType::INTEGER)
                        )
                    )
                )
                ->orderBy('id')
        );
    }
}
