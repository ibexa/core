<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Core\Persistence\Legacy\User\Gateway;
use Ibexa\Core\Persistence\Legacy\UserPreference\Gateway\DoctrineDatabase;

return [
    Gateway::USER_TABLE => [
        0 => [
            'contentobject_id' => '14',
            'email' => 'foo@example.com',
            'login' => 'admin',
            'password_hash' => 'publish',
            'password_hash_type' => '5',
        ],
    ],
    DoctrineDatabase::TABLE_USER_PREFERENCES => [
        0 => [
            'id' => 1,
            'user_id' => 14,
            'name' => 'timezone',
            'value' => 'America/New_York',
        ],
        1 => [
            'id' => 2,
            'user_id' => 14,
            'name' => 'setting_1',
            'value' => 'value_1',
        ],
        2 => [
            'id' => 3,
            'user_id' => 14,
            'name' => 'setting_2',
            'value' => 'value_2',
        ],
    ],
];
