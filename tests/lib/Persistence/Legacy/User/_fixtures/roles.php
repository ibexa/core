<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\User\Role\Gateway;

return [
    Gateway::ROLE_TABLE => [
        [
            'id' => '1',
            'is_new' => '0',
            'name' => 'Anonymous',
            'value' => ' ',
            'version' => '0',
        ],
        [
            'id' => '2',
            'is_new' => '0',
            'name' => 'Administrator',
            'value' => '*',
            'version' => '0',
        ],
        [
            'id' => '3',
            'is_new' => '0',
            'name' => 'Editor',
            'value' => ' ',
            'version' => '0',
        ],
        [
            'id' => '4',
            'is_new' => '0',
            'name' => 'Partner',
            'value' => null,
            'version' => '0',
        ],
        [
            'id' => '5',
            'is_new' => '0',
            'name' => 'Member',
            'value' => null,
            'version' => '0',
        ],
    ],
    Gateway::USER_ROLE_TABLE => [
        [
            'contentobject_id' => '12',
            'id' => '25',
            'limit_identifier' => '',
            'limit_value' => '',
            'role_id' => '2',
        ],
        [
            'contentobject_id' => '11',
            'id' => '28',
            'limit_identifier' => '',
            'limit_value' => '',
            'role_id' => '1',
        ],
        [
            'contentobject_id' => '42',
            'id' => '31',
            'limit_identifier' => '',
            'limit_value' => '',
            'role_id' => '1',
        ],
        [
            'contentobject_id' => '13',
            'id' => '32',
            'limit_identifier' => 'Subtree',
            'limit_value' => '/1/2/',
            'role_id' => '3',
        ],
        [
            'contentobject_id' => '13',
            'id' => '33',
            'limit_identifier' => 'Subtree',
            'limit_value' => '/1/43/',
            'role_id' => '3',
        ],
        [
            'contentobject_id' => '11',
            'id' => '34',
            'limit_identifier' => '',
            'limit_value' => '',
            'role_id' => '5',
        ],
        [
            'contentobject_id' => '59',
            'id' => '35',
            'limit_identifier' => '',
            'limit_value' => '',
            'role_id' => '4',
        ],
        [
            'contentobject_id' => '59',
            'id' => '36',
            'limit_identifier' => '',
            'limit_value' => '',
            'role_id' => '5',
        ],
        [
            'contentobject_id' => '59',
            'id' => '37',
            'limit_identifier' => '',
            'limit_value' => '',
            'role_id' => '1',
        ],
        [
            'contentobject_id' => '13',
            'id' => '38',
            'limit_identifier' => '',
            'limit_value' => '',
            'role_id' => '5',
        ],
        [
            'contentobject_id' => '13',
            'id' => '39',
            'limit_identifier' => 'Section',
            'limit_value' => '2',
            'role_id' => '5',
        ],
        [
            'contentobject_id' => '11',
            'id' => '40',
            'limit_identifier' => 'Section',
            'limit_value' => '3',
            'role_id' => '4',
        ],
    ],
    ContentGateway::CONTENT_ITEM_TABLE => [
        [
            'id' => '11',
        ],
        [
            'id' => '42',
        ],
    ],
];
