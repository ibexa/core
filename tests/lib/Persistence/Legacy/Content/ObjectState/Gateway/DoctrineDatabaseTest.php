<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\ObjectState\Gateway;

use Ibexa\Contracts\Core\Persistence\Content\ObjectState;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTest extends LanguageAwareTestCase
{
    /**
     * Database gateway to test.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway\DoctrineDatabase
     */
    protected $databaseGateway;

    /**
     * Language mask generator.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator
     */
    protected $languageMaskGenerator;

    /**
     * Inserts DB fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../../_fixtures/contentobjects.php'
        );

        $this->insertDatabaseFixture(
            __DIR__ . '/../../_fixtures/objectstates.php'
        );
    }

    public function testLoadObjectStateData()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadObjectStateData(1);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_default_language_id' => 2,
                    'ibexa_object_state_group_id' => 2,
                    'ibexa_object_state_id' => 1,
                    'ibexa_object_state_identifier' => 'not_locked',
                    'ibexa_object_state_language_mask' => 3,
                    'ibexa_object_state_priority' => 0,
                    'ibexa_object_state_language_description' => '',
                    'ibexa_object_state_language_language_id' => 3,
                    'ibexa_object_state_language_name' => 'Not locked',
                ],
            ],
            $result
        );
    }

    public function testLoadObjectStateDataByIdentifier()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadObjectStateDataByIdentifier('not_locked', 2);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_default_language_id' => 2,
                    'ibexa_object_state_group_id' => 2,
                    'ibexa_object_state_id' => 1,
                    'ibexa_object_state_identifier' => 'not_locked',
                    'ibexa_object_state_language_mask' => 3,
                    'ibexa_object_state_priority' => 0,
                    'ibexa_object_state_language_description' => '',
                    'ibexa_object_state_language_language_id' => 3,
                    'ibexa_object_state_language_name' => 'Not locked',
                ],
            ],
            $result
        );
    }

    public function testLoadObjectStateListData()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadObjectStateListData(2);

        self::assertEquals(
            [
                [
                    [
                        'ibexa_object_state_default_language_id' => 2,
                        'ibexa_object_state_group_id' => 2,
                        'ibexa_object_state_id' => 1,
                        'ibexa_object_state_identifier' => 'not_locked',
                        'ibexa_object_state_language_mask' => 3,
                        'ibexa_object_state_priority' => 0,
                        'ibexa_object_state_language_description' => '',
                        'ibexa_object_state_language_language_id' => 3,
                        'ibexa_object_state_language_name' => 'Not locked',
                    ],
                ],
                [
                    [
                        'ibexa_object_state_default_language_id' => 2,
                        'ibexa_object_state_group_id' => 2,
                        'ibexa_object_state_id' => 2,
                        'ibexa_object_state_identifier' => 'locked',
                        'ibexa_object_state_language_mask' => 3,
                        'ibexa_object_state_priority' => 1,
                        'ibexa_object_state_language_description' => '',
                        'ibexa_object_state_language_language_id' => 3,
                        'ibexa_object_state_language_name' => 'Locked',
                    ],
                ],
            ],
            $result
        );
    }

    public function testLoadObjectStateGroupData()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadObjectStateGroupData(2);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_group_default_language_id' => 2,
                    'ibexa_object_state_group_id' => 2,
                    'ibexa_object_state_group_identifier' => 'ibexa_lock',
                    'ibexa_object_state_group_language_mask' => 3,
                    'ibexa_object_state_group_language_description' => '',
                    'ibexa_object_state_group_language_language_id' => 3,
                    'ibexa_object_state_group_language_real_language_id' => 2,
                    'ibexa_object_state_group_language_name' => 'Lock',
                ],
            ],
            $result
        );
    }

    public function testLoadObjectStateGroupDataByIdentifier()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadObjectStateGroupDataByIdentifier('ibexa_lock');

        self::assertEquals(
            [
                [
                    'ibexa_object_state_group_default_language_id' => 2,
                    'ibexa_object_state_group_id' => 2,
                    'ibexa_object_state_group_identifier' => 'ibexa_lock',
                    'ibexa_object_state_group_language_mask' => 3,
                    'ibexa_object_state_group_language_description' => '',
                    'ibexa_object_state_group_language_language_id' => 3,
                    'ibexa_object_state_group_language_real_language_id' => 2,
                    'ibexa_object_state_group_language_name' => 'Lock',
                ],
            ],
            $result
        );
    }

    public function testLoadObjectStateGroupListData()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadObjectStateGroupListData(0, -1);

        self::assertEquals(
            [
                [
                    [
                        'ibexa_object_state_group_default_language_id' => 2,
                        'ibexa_object_state_group_id' => 2,
                        'ibexa_object_state_group_identifier' => 'ibexa_lock',
                        'ibexa_object_state_group_language_mask' => 3,
                        'ibexa_object_state_group_language_description' => '',
                        'ibexa_object_state_group_language_language_id' => 3,
                        'ibexa_object_state_group_language_real_language_id' => 2,
                        'ibexa_object_state_group_language_name' => 'Lock',
                    ],
                ],
            ],
            $result
        );
    }

    public function testInsertObjectState()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->insertObjectState($this->getObjectStateFixture(), 2);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_default_language_id' => 4,
                    'ibexa_object_state_group_id' => 2,
                    // The new state should be added with state ID = 3
                    'ibexa_object_state_id' => 3,
                    'ibexa_object_state_identifier' => 'test_state',
                    'ibexa_object_state_language_mask' => 5,
                    // The new state should have priority = 2
                    'ibexa_object_state_priority' => 2,
                    'ibexa_object_state_language_description' => 'Test state description',
                    'ibexa_object_state_language_language_id' => 5,
                    'ibexa_object_state_language_name' => 'Test state',
                ],
            ],
            // The new state should be added with state ID = 3
            $this->getDatabaseGateway()->loadObjectStateData(3)
        );
    }

    public function testInsertObjectStateInEmptyGroup()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->insertObjectStateGroup($this->getObjectStateGroupFixture());
        $gateway->insertObjectState($this->getObjectStateFixture(), 3);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_default_language_id' => 4,
                    // New group should be added with group ID = 3
                    'ibexa_object_state_group_id' => 3,
                    // The new state should be added with state ID = 3
                    'ibexa_object_state_id' => 3,
                    'ibexa_object_state_identifier' => 'test_state',
                    'ibexa_object_state_language_mask' => 5,
                    // The new state should have priority = 0
                    'ibexa_object_state_priority' => 0,
                    'ibexa_object_state_language_description' => 'Test state description',
                    'ibexa_object_state_language_language_id' => 5,
                    'ibexa_object_state_language_name' => 'Test state',
                ],
            ],
            // The new state should be added with state ID = 3
            $this->getDatabaseGateway()->loadObjectStateData(3)
        );

        self::assertEquals(
            // 185 is the number of objects in the fixture
            185,
            $gateway->getContentCount(3)
        );
    }

    public function testUpdateObjectState()
    {
        $gateway = $this->getDatabaseGateway();

        $objectStateFixture = $this->getObjectStateFixture();
        $objectStateFixture->id = 1;

        $gateway->updateObjectState($objectStateFixture);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_default_language_id' => 4,
                    'ibexa_object_state_group_id' => 2,
                    'ibexa_object_state_id' => 1,
                    'ibexa_object_state_identifier' => 'test_state',
                    'ibexa_object_state_language_mask' => 5,
                    'ibexa_object_state_priority' => 0,
                    'ibexa_object_state_language_description' => 'Test state description',
                    'ibexa_object_state_language_language_id' => 5,
                    'ibexa_object_state_language_name' => 'Test state',
                ],
            ],
            $this->getDatabaseGateway()->loadObjectStateData(1)
        );
    }

    public function testDeleteObjectState()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->deleteObjectState(1);

        self::assertEquals(
            [],
            $this->getDatabaseGateway()->loadObjectStateData(1)
        );
    }

    public function testUpdateObjectStateLinks()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->updateObjectStateLinks(1, 2);

        self::assertSame(0, $gateway->getContentCount(1));
        self::assertSame(185, $gateway->getContentCount(2));
    }

    public function testDeleteObjectStateLinks()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->deleteObjectStateLinks(1);

        self::assertSame(0, $gateway->getContentCount(1));
    }

    public function testInsertObjectStateGroup()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->insertObjectStateGroup($this->getObjectStateGroupFixture());

        self::assertEquals(
            [
                [
                    'ibexa_object_state_group_default_language_id' => 4,
                    // The new state group should be added with state group ID = 3
                    'ibexa_object_state_group_id' => 3,
                    'ibexa_object_state_group_identifier' => 'test_group',
                    'ibexa_object_state_group_language_mask' => 5,
                    'ibexa_object_state_group_language_description' => 'Test group description',
                    'ibexa_object_state_group_language_language_id' => 5,
                    'ibexa_object_state_group_language_real_language_id' => 4,
                    'ibexa_object_state_group_language_name' => 'Test group',
                ],
            ],
            // The new state group should be added with state group ID = 3
            $this->getDatabaseGateway()->loadObjectStateGroupData(3)
        );
    }

    public function testUpdateObjectStateGroup()
    {
        $gateway = $this->getDatabaseGateway();

        $groupFixture = $this->getObjectStateGroupFixture();
        $groupFixture->id = 2;

        $gateway->updateObjectStateGroup($groupFixture);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_group_default_language_id' => 4,
                    'ibexa_object_state_group_id' => 2,
                    'ibexa_object_state_group_identifier' => 'test_group',
                    'ibexa_object_state_group_language_mask' => 5,
                    'ibexa_object_state_group_language_description' => 'Test group description',
                    'ibexa_object_state_group_language_language_id' => 5,
                    'ibexa_object_state_group_language_real_language_id' => 4,
                    'ibexa_object_state_group_language_name' => 'Test group',
                ],
            ],
            $this->getDatabaseGateway()->loadObjectStateGroupData(2)
        );
    }

    public function testDeleteObjectStateGroup()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->deleteObjectStateGroup(2);

        self::assertEquals(
            [],
            $this->getDatabaseGateway()->loadObjectStateGroupData(2)
        );
    }

    public function testSetContentState()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->setContentState(42, 2, 2);

        $this->assertQueryResult(
            [
                [
                    'contentobject_id' => 42,
                    'contentobject_state_id' => 2,
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('contentobject_id', 'contentobject_state_id')
                ->from(Gateway::OBJECT_STATE_LINK_TABLE)
                ->where('contentobject_id = 42')
        );
    }

    public function testLoadObjectStateDataForContent()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadObjectStateDataForContent(42, 2);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_default_language_id' => 2,
                    'ibexa_object_state_group_id' => 2,
                    'ibexa_object_state_id' => 1,
                    'ibexa_object_state_identifier' => 'not_locked',
                    'ibexa_object_state_language_mask' => 3,
                    'ibexa_object_state_priority' => 0,
                    'ibexa_object_state_language_description' => '',
                    'ibexa_object_state_language_language_id' => 3,
                    'ibexa_object_state_language_name' => 'Not locked',
                ],
            ],
            $result
        );
    }

    public function testGetContentCount()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->getContentCount(1);

        // 185 is the number of objects in the fixture
        self::assertEquals(185, $result);
    }

    public function testUpdateObjectStatePriority()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->updateObjectStatePriority(1, 10);

        $objectStateData = $gateway->loadObjectStateData(1);

        self::assertEquals(
            [
                [
                    'ibexa_object_state_default_language_id' => 2,
                    'ibexa_object_state_group_id' => 2,
                    'ibexa_object_state_id' => 1,
                    'ibexa_object_state_identifier' => 'not_locked',
                    'ibexa_object_state_language_mask' => 3,
                    'ibexa_object_state_priority' => 10,
                    'ibexa_object_state_language_description' => '',
                    'ibexa_object_state_language_language_id' => 3,
                    'ibexa_object_state_language_name' => 'Not locked',
                ],
            ],
            $objectStateData
        );
    }

    /**
     * Returns an object state fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState
     */
    protected function getObjectStateFixture()
    {
        $objectState = new ObjectState();
        $objectState->identifier = 'test_state';
        $objectState->defaultLanguage = 'eng-GB';
        $objectState->languageCodes = ['eng-GB'];
        $objectState->name = ['eng-GB' => 'Test state'];
        $objectState->description = ['eng-GB' => 'Test state description'];

        return $objectState;
    }

    /**
     * Returns an object state group fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group
     */
    protected function getObjectStateGroupFixture()
    {
        $group = new Group();
        $group->identifier = 'test_group';
        $group->defaultLanguage = 'eng-GB';
        $group->languageCodes = ['eng-GB'];
        $group->name = ['eng-GB' => 'Test group'];
        $group->description = ['eng-GB' => 'Test group description'];

        return $group;
    }

    /**
     * Returns a ready to test DoctrineDatabase gateway.
     */
    protected function getDatabaseGateway(): DoctrineDatabase
    {
        if (!isset($this->databaseGateway)) {
            $this->databaseGateway = new DoctrineDatabase(
                $this->getDatabaseConnection(),
                $this->getLanguageMaskGenerator()
            );
        }

        return $this->databaseGateway;
    }
}
