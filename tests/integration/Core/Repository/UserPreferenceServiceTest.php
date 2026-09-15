<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreference;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreferenceList;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreferenceSetStruct;

/**
 * Test case for the UserPreferenceService.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Contracts\Core\Repository\UserPreferenceService::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\UserPreferenceService::class, 'loadUserPreferences()')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\UserPreferenceService::class, 'getUserPreference()')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\UserPreferenceService::class, 'setUserPreference()')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\UserPreferenceService::class, 'getUserPreferenceCount()')]
class UserPreferenceServiceTest extends BaseTestCase
{
    public function testLoadUserPreferences()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $userPreferenceService = $repository->getUserPreferenceService();
        $userPreferenceList = $userPreferenceService->loadUserPreferences(0, 25);
        /* END: Use Case */

        self::assertInstanceOf(UserPreferenceList::class, $userPreferenceList);
        self::assertIsArray($userPreferenceList->items);
        self::assertIsInt($userPreferenceList->totalCount);
        self::assertEquals(5, $userPreferenceList->totalCount);
    }

    public function testGetUserPreference()
    {
        $repository = $this->getRepository();

        $userPreferenceName = 'setting_1';

        /* BEGIN: Use Case */
        $userPreferenceService = $repository->getUserPreferenceService();
        // $userPreferenceName is the name of an existing preference
        $userPreference = $userPreferenceService->getUserPreference($userPreferenceName);
        /* END: Use Case */

        self::assertInstanceOf(UserPreference::class, $userPreference);
        self::assertEquals($userPreferenceName, $userPreference->name);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetUserPreference')]
    public function testSetUserPreference()
    {
        $repository = $this->getRepository();

        $userPreferenceName = 'timezone';

        /* BEGIN: Use Case */
        $userPreferenceService = $repository->getUserPreferenceService();

        $setStruct = new UserPreferenceSetStruct([
            'name' => $userPreferenceName,
            'value' => 'America/New_York',
        ]);

        $userPreferenceService->setUserPreference([$setStruct]);
        $userPreference = $userPreferenceService->getUserPreference($userPreferenceName);
        /* END: Use Case */

        self::assertInstanceOf(UserPreference::class, $userPreference);
        self::assertEquals($userPreferenceName, $userPreference->name);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testSetUserPreference')]
    public function testSetUserPreferenceThrowsInvalidArgumentExceptionOnInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $userPreferenceService = $repository->getUserPreferenceService();

        $setStruct = new UserPreferenceSetStruct([
            'name' => 'setting',
            'value' => new \stdClass(),
        ]);

        // This call will fail because value is not specified
        $userPreferenceService->setUserPreference([$setStruct]);
        /* END: Use Case */
    }

    #[\PHPUnit\Framework\Attributes\Depends('testSetUserPreference')]
    public function testSetUserPreferenceThrowsInvalidArgumentExceptionOnEmptyName()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $userPreferenceService = $repository->getUserPreferenceService();

        $setStruct = new UserPreferenceSetStruct([
            'value' => 'value',
        ]);

        // This call will fail because value is not specified
        $userPreferenceService->setUserPreference([$setStruct]);
        /* END: Use Case */
    }

    public function testGetUserPreferenceCount()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $userPreferenceService = $repository->getUserPreferenceService();
        $userPreferenceCount = $userPreferenceService->getUserPreferenceCount();
        /* END: Use Case */

        self::assertEquals(5, $userPreferenceCount);
    }
}
