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
use Ibexa\Core\Repository\UserPreferenceService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Test case for the UserPreferenceService.
 */
#[CoversClass(UserPreferenceService::class)]
#[CoversMethod(UserPreferenceService::class, 'loadUserPreferences')]
#[CoversMethod(UserPreferenceService::class, 'getUserPreference')]
#[CoversMethod(UserPreferenceService::class, 'setUserPreference')]
#[CoversMethod(UserPreferenceService::class, 'getUserPreferenceCount')]
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

    #[Depends('testGetUserPreference')]
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

    #[Depends('testSetUserPreference')]
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

    #[Depends('testSetUserPreference')]
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
