<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Translation\Policy;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

final class PolicyTranslationDefinitionProvider implements TranslationContainerInterface
{
    private const TRANSLATION_DOMAIN = 'forms';

    /**
     * @return array<Message>
     */
    public static function getTranslationMessages(): array
    {
        return [
            (new Message('role.policy.all_modules_all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('All modules / All functions'),
            (new Message('role.policy.content', self::TRANSLATION_DOMAIN))
                ->setDesc('Content'),
            (new Message('role.policy.content.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / All functions'),
            (new Message('role.policy.content.cleantrash', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Clean trash'),
            (new Message('role.policy.content.create', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Create'),
            (new Message('role.policy.content.diff', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Diff'),
            (new Message('role.policy.content.edit', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Edit'),
            (new Message('role.policy.content.hide', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Hide'),
            (new Message('role.policy.content.view_embed', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / View embed'),
            (new Message('role.policy.content.manage_locations', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Manage locations'),
            (new Message('role.policy.content.pendinglist', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Pending list'),
            (new Message('role.policy.content.publish', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Publish'),
            (new Message('role.policy.content.read', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Read'),
            (new Message('role.policy.content.remove', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Remove'),
            (new Message('role.policy.content.restore', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Restore'),
            (new Message('role.policy.content.reverserelatedlist', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Reverse related list'),
            (new Message('role.policy.content.translate', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Translate'),
            (new Message('role.policy.content.translations', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Translations'),
            (new Message('role.policy.content.unlock', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Unlock'),
            (new Message('role.policy.content.urltranslator', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Url translator'),
            (new Message('role.policy.content.versionread', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Version read'),
            (new Message('role.policy.content.versionremove', self::TRANSLATION_DOMAIN))
                ->setDesc('Content / Version remove'),

            (new Message('role.policy.class', self::TRANSLATION_DOMAIN))
                ->setDesc('Content type'),
            (new Message('role.policy.class.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('Content type / All functions'),
            (new Message('role.policy.class.create', self::TRANSLATION_DOMAIN))
                ->setDesc('Content type / Create'),
            (new Message('role.policy.class.delete', self::TRANSLATION_DOMAIN))
                ->setDesc('Content type / Delete'),
            (new Message('role.policy.class.update', self::TRANSLATION_DOMAIN))
                ->setDesc('Content type / Update'),

            (new Message('role.policy.state', self::TRANSLATION_DOMAIN))
                ->setDesc('State'),
            (new Message('role.policy.state.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('State / All functions'),
            (new Message('role.policy.state.administrate', self::TRANSLATION_DOMAIN))
                ->setDesc('State / Administrate'),
            (new Message('role.policy.state.assign', self::TRANSLATION_DOMAIN))
                ->setDesc('State / Assign'),

            (new Message('role.policy.role', self::TRANSLATION_DOMAIN))
                ->setDesc('Role'),
            (new Message('role.policy.role.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('Role / All functions'),
            (new Message('role.policy.role.assign', self::TRANSLATION_DOMAIN))
                ->setDesc('Role / Assign'),
            (new Message('role.policy.role.create', self::TRANSLATION_DOMAIN))
                ->setDesc('Role / Create'),
            (new Message('role.policy.role.delete', self::TRANSLATION_DOMAIN))
                ->setDesc('Role / Delete'),
            (new Message('role.policy.role.read', self::TRANSLATION_DOMAIN))
                ->setDesc('Role / Read'),
            (new Message('role.policy.role.update', self::TRANSLATION_DOMAIN))
                ->setDesc('Role / Update'),

            (new Message('role.policy.section', self::TRANSLATION_DOMAIN))
                ->setDesc('Section'),
            (new Message('role.policy.section.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('Section / All functions'),
            (new Message('role.policy.section.assign', self::TRANSLATION_DOMAIN))
                ->setDesc('Section / Assign'),
            (new Message('role.policy.section.edit', self::TRANSLATION_DOMAIN))
                ->setDesc('Section / Edit'),
            (new Message('role.policy.section.view', self::TRANSLATION_DOMAIN))
                ->setDesc('Section / View'),

            (new Message('role.policy.setup', self::TRANSLATION_DOMAIN))
                ->setDesc('Setup'),
            (new Message('role.policy.setup.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('Setup / All functions'),
            (new Message('role.policy.setup.administrate', self::TRANSLATION_DOMAIN))
                ->setDesc('Setup / Administrate'),
            (new Message('role.policy.setup.install', self::TRANSLATION_DOMAIN))
                ->setDesc('Setup / Install'),
            (new Message('role.policy.setup.setup', self::TRANSLATION_DOMAIN))
                ->setDesc('Setup / Setup'),
            (new Message('role.policy.setup.system_info', self::TRANSLATION_DOMAIN))
                ->setDesc('Setup / System info'),

            (new Message('role.policy.url', self::TRANSLATION_DOMAIN))
                ->setDesc('URL'),
            (new Message('role.policy.url.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('URL / All functions'),
            (new Message('role.policy.url.view', self::TRANSLATION_DOMAIN))
                ->setDesc('URL / View'),
            (new Message('role.policy.url.update', self::TRANSLATION_DOMAIN))
                ->setDesc('URL / Update'),

            (new Message('role.policy.user', self::TRANSLATION_DOMAIN))
                ->setDesc('User'),
            (new Message('role.policy.user.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('User / All functions'),
            (new Message('role.policy.user.activation', self::TRANSLATION_DOMAIN))
                ->setDesc('User / Activation'),
            (new Message('role.policy.user.invite', self::TRANSLATION_DOMAIN))
                ->setDesc('User / Invite'),
            (new Message('role.policy.user.login', self::TRANSLATION_DOMAIN))
                ->setDesc('User / Login'),
            (new Message('role.policy.user.password', self::TRANSLATION_DOMAIN))
                ->setDesc('User / Password'),
            (new Message('role.policy.user.preferences', self::TRANSLATION_DOMAIN))
                ->setDesc('User / Preferences'),
            (new Message('role.policy.user.register', self::TRANSLATION_DOMAIN))
                ->setDesc('User / Register'),
            (new Message('role.policy.user.selfedit', self::TRANSLATION_DOMAIN))
                ->setDesc('User / Self edit'),

            (new Message('role.policy.user', self::TRANSLATION_DOMAIN))
                ->setDesc('User'),
            (new Message('role.policy.user.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('User / All functions'),
            (new Message('role.policy.user.update', self::TRANSLATION_DOMAIN))
                ->setDesc('User / Update'),
            (new Message('role.policy.user.view', self::TRANSLATION_DOMAIN))
                ->setDesc('User / View'),

            (new Message('role.policy.setting', self::TRANSLATION_DOMAIN))
                ->setDesc('Setting'),
            (new Message('role.policy.setting.all_functions', self::TRANSLATION_DOMAIN))
                ->setDesc('Setting / All functions'),
            (new Message('role.policy.setting.create', self::TRANSLATION_DOMAIN))
                ->setDesc('Setting / Create'),
            (new Message('role.policy.setting.remove', self::TRANSLATION_DOMAIN))
                ->setDesc('Setting / Remove'),
            (new Message('role.policy.setting.update', self::TRANSLATION_DOMAIN))
                ->setDesc('Setting / Update'),
        ];
    }
}
