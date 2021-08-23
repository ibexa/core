<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;
use Ibexa\Contracts\Core\Persistence\UserPreference\Handler as SPIUserPreferenceHandler;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreference as SPIUserPreference;

/**
 * Test case for Persistence\Cache\UserPreferenceHandler.
 */
class UserPreferenceHandlerTest extends AbstractInMemoryCacheHandlerTest
{
    /**
     * {@inheritdoc}
     */
    public function getHandlerMethodName(): string
    {
        return 'userPreferenceHandler';
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlerClassName(): string
    {
        return SPIUserPreferenceHandler::class;
    }

    /**
     * {@inheritdoc}
     */
    public function providerForUnCachedMethods(): array
    {
        $userId = 7;
        $name = 'setting';
        $userPreferenceCount = 10;

        // string $method, array $arguments, array? $tags, array? $key, mixed? $returnValue
        return [
            [
                'setUserPreference',
                [
                    new UserPreferenceSetStruct([
                        'userId' => $userId,
                        'name' => $name,
                    ]),
                ],
                null,
                [
                    'ez-user-preference-' . $userId . '-' . $name,
                ],
                new SPIUserPreference(),
            ],
            [
                'loadUserPreferences', [$userId, 0, 25], null, null, [],
            ],
            [
                'countUserPreferences',
                [
                    $userId,
                ],
                null,
                null,
                $userPreferenceCount,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function providerForCachedLoadMethods(): array
    {
        $userId = 7;
        $name = 'setting';

        // string $method, array $arguments, string $key, mixed? $data
        return [
            [
                'getUserPreferenceByUserIdAndName',
                [
                    $userId,
                    $name,
                ],
                'ez-user-preference-' . $userId . '-' . $name,
                new SPIUserPreference(['userId' => $userId, 'name' => $name]),
            ],
        ];
    }
}

class_alias(UserPreferenceHandlerTest::class, 'eZ\Publish\Core\Persistence\Cache\Tests\UserPreferenceHandlerTest');
