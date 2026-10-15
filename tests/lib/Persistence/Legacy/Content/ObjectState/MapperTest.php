<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\ObjectState;

use Ibexa\Contracts\Core\Persistence\Content\ObjectState;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\InputStruct;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Mapper;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\ObjectState\Mapper
 */
class MapperTest extends LanguageAwareTestCase
{
    public function testCreateObjectStateFromData()
    {
        $mapper = $this->getMapper();

        $rows = $this->getObjectStateRowsFixture();

        $result = $mapper->createObjectStateFromData($rows);

        $this->assertStructsEqual(
            $this->getObjectStateFixture(),
            $result,
            ['identifier', 'defaultLanguage', 'languageCodes', 'name', 'description']
        );
    }

    public function testCreateObjectStateListFromData()
    {
        $mapper = $this->getMapper();

        $rows = [$this->getObjectStateRowsFixture()];

        $result = $mapper->createObjectStateListFromData($rows);

        $this->assertStructsEqual(
            $this->getObjectStateFixture(),
            $result[0],
            ['identifier', 'defaultLanguage', 'languageCodes', 'name', 'description']
        );
    }

    public function testCreateObjectStateGroupFromData()
    {
        $mapper = $this->getMapper();

        $rows = $this->getObjectStateGroupRowsFixture();

        $result = $mapper->createObjectStateGroupFromData($rows);

        $this->assertStructsEqual(
            $this->getObjectStateGroupFixture(),
            $result,
            ['identifier', 'defaultLanguage', 'languageCodes', 'name', 'description']
        );
    }

    public function testCreateObjectStateGroupListFromData()
    {
        $mapper = $this->getMapper();

        $rows = [$this->getObjectStateGroupRowsFixture()];

        $result = $mapper->createObjectStateGroupListFromData($rows);

        $this->assertStructsEqual(
            $this->getObjectStateGroupFixture(),
            $result[0],
            ['identifier', 'defaultLanguage', 'languageCodes', 'name', 'description']
        );
    }

    public function testCreateObjectStateFromInputStruct()
    {
        $mapper = $this->getMapper();

        $inputStruct = $this->getObjectStateInputStructFixture();

        $result = $mapper->createObjectStateFromInputStruct($inputStruct);

        $this->assertStructsEqual(
            $this->getObjectStateFixture(),
            $result,
            ['identifier', 'defaultLanguage', 'languageCodes', 'name', 'description']
        );
    }

    public function testCreateObjectStateGroupFromInputStruct()
    {
        $mapper = $this->getMapper();

        $inputStruct = $this->getObjectStateGroupInputStructFixture();

        $result = $mapper->createObjectStateGroupFromInputStruct($inputStruct);

        $this->assertStructsEqual(
            $this->getObjectStateGroupFixture(),
            $result,
            ['identifier', 'defaultLanguage', 'languageCodes', 'name', 'description']
        );
    }

    /**
     * Returns a Mapper.
     *
     * @return Mapper
     */
    protected function getMapper()
    {
        return new Mapper(
            $this->getLanguageHandler()
        );
    }

    /**
     * Returns an object state result rows fixture.
     *
     * @return array[][]
     */
    protected function getObjectStateRowsFixture()
    {
        return [
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
        ];
    }

    /**
     * Returns an object state group result rows fixture.
     *
     * @return array[][]
     */
    protected function getObjectStateGroupRowsFixture()
    {
        return [
            [
                'ibexa_object_state_group_default_language_id' => 2,
                'ibexa_object_state_group_id' => 1,
                'ibexa_object_state_group_identifier' => 'ibexa_lock',
                'ibexa_object_state_group_language_mask' => 3,
                'ibexa_object_state_group_language_description' => '',
                'ibexa_object_state_group_language_language_id' => 3,
                'ibexa_object_state_group_language_real_language_id' => 2,
                'ibexa_object_state_group_language_name' => 'Lock',
            ],
        ];
    }

    /**
     * Returns an object state fixture.
     *
     * @return ObjectState
     */
    protected function getObjectStateFixture()
    {
        $objectState = new ObjectState();
        $objectState->identifier = 'not_locked';
        $objectState->defaultLanguage = 'eng-US';
        $objectState->languageCodes = ['eng-US'];
        $objectState->name = ['eng-US' => 'Not locked'];
        $objectState->description = ['eng-US' => ''];

        return $objectState;
    }

    /**
     * Returns an object state group fixture.
     *
     * @return Group
     */
    protected function getObjectStateGroupFixture()
    {
        $group = new Group();
        $group->identifier = 'ibexa_lock';
        $group->defaultLanguage = 'eng-US';
        $group->languageCodes = ['eng-US'];
        $group->name = ['eng-US' => 'Lock'];
        $group->description = ['eng-US' => ''];

        return $group;
    }

    /**
     * Returns the InputStruct fixture for creating object states.
     *
     * @return InputStruct
     */
    protected function getObjectStateInputStructFixture()
    {
        $inputStruct = new InputStruct();

        $inputStruct->defaultLanguage = 'eng-US';
        $inputStruct->identifier = 'not_locked';
        $inputStruct->name = ['eng-US' => 'Not locked'];
        $inputStruct->description = ['eng-US' => ''];

        return $inputStruct;
    }

    /**
     * Returns the InputStruct fixture for creating object state groups.
     *
     * @return InputStruct
     */
    protected function getObjectStateGroupInputStructFixture()
    {
        $inputStruct = new InputStruct();

        $inputStruct->defaultLanguage = 'eng-US';
        $inputStruct->identifier = 'ibexa_lock';
        $inputStruct->name = ['eng-US' => 'Lock'];
        $inputStruct->description = ['eng-US' => ''];

        return $inputStruct;
    }
}
