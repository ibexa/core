<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\UserPreference\Handler as SPIUserPreferenceHandler;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreference as SPIUserPreference;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;

/**
 * Test case for Persistence\Cache\UserPreferenceHandler.
 */
class UserPreferenceHandlerTest extends AbstractInMemoryCacheHandlerTestCase
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

    public function providerForUnCachedMethods(): iterable
    {
        $userId = 7;
        $name = 'setting';
        $userPreferenceCount = 10;

        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        yield 'setUserPreference' => [
            'setUserPreference',
            [
                new UserPreferenceSetStruct([
                                                'userId' => $userId,
                                                'name' => $name,
                                            ]),
            ],
            null,
            [
                ['user_preference_with_suffix', [$userId, $name], true],
            ],
            null,
            [
                'ibx-up-' . $userId . '-' . $name,
            ],
            new SPIUserPreference(),
        ];

        yield 'loadUserPreferences' => ['loadUserPreferences', [$userId, 0, 25], null, null, null, null, []];

        yield 'countUserPreferences' => [
            'countUserPreferences',
            [
                $userId,
            ],
            null,
            null,
            null,
            null,
            $userPreferenceCount,
        ];
    }

    public function providerForCachedLoadMethodsHit(): iterable
    {
        $userId = 7;
        $name = 'setting';

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        yield 'getUserPreferenceByUserIdAndName' => [
            'getUserPreferenceByUserIdAndName',
            [
                $userId,
                $name,
            ],
            'ibx-up-' . $userId . '-' . $name,
            null,
            null,
            [['user_preference', [], true]],
            ['ibx-up'],
            new SPIUserPreference(['userId' => $userId, 'name' => $name]),
        ];
    }

    public function providerForCachedLoadMethodsMiss(): iterable
    {
        $userId = 7;
        $name = 'setting';

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        yield 'getUserPreferenceByUserIdAndName' => [
            'getUserPreferenceByUserIdAndName',
            [
                $userId,
                $name,
            ],
            'ibx-up-' . $userId . '-' . $name,
            null,
            null,
            [['user_preference', [], true]],
            ['ibx-up'],
            new SPIUserPreference(['userId' => $userId, 'name' => $name]),
        ];
    }
}
