<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\Repository;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\RepositoryConfigParserInterface;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @internal
 *
 * Configuration parser for password hash configuration.
 *
 * Example configuration:
 * ```yaml
 * ibexa:
 *   system:
 *      default: # configuration per siteaccess or siteaccess group
 *          password_hash:
 *              default_type: !php/const \Ibexa\Contracts\Core\Repository\Values\User\User::PASSWORD_HASH_ARGON2I
 *              update_type_on_change: false
 * ```
 */
final class PasswordHash implements RepositoryConfigParserInterface
{
    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('password_hash')
                ->info('Password hash options')
                ->children()
                    ->integerNode('default_type')
                        ->info('Default password hash type, see the constants in Ibexa\Contracts\Core\Repository\Values\User\User.')
                        ->example('!php/const:Ibexa\Contracts\Core\Repository\Values\User\User::PASSWORD_HASH_PHP_DEFAULT')
                        ->defaultValue(User::PASSWORD_HASH_PHP_DEFAULT)
                        ->validate()
                            ->ifTrue(static function ($value): bool {
                                $hashType = (int) $value;

                                if ($hashType === User::PASSWORD_HASH_ARGON2I) {
                                    return !defined('PASSWORD_ARGON2I');
                                } elseif ($hashType === User::PASSWORD_HASH_ARGON2ID) {
                                    return !defined('PASSWORD_ARGON2ID');
                                }

                                return !in_array($hashType, User::SUPPORTED_PASSWORD_HASHES, true);
                            })
                            ->thenInvalid('Invalid password hash type "%s". If you tried to use Argon2, make sure it\'s compiled in PHP.')
                        ->end()
                    ->end()
                    ->booleanNode('update_type_on_change')
                        ->info('Whether the password hash type should be changed when the password is changed if it differs from the default type.')
                        ->example('false')
                        ->defaultFalse()
                    ->end()
                ->end()
            ->end();
    }
}
