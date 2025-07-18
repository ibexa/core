<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\User;

use Ibexa\Contracts\Core\Repository\PasswordHashService as PasswordHashServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Repository\User\Exception\PasswordHashTypeNotCompiled;
use Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType;

/**
 * @internal
 */
final class PasswordHashService implements PasswordHashServiceInterface
{
    private int $defaultHashType;

    private ConfigResolverInterface $configResolver;

    public function __construct(int $hashType = User::DEFAULT_PASSWORD_HASH)
    {
        $this->defaultHashType = $hashType;
    }

    public function setConfigResolver(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    public function getSupportedHashTypes(): array
    {
        return User::SUPPORTED_PASSWORD_HASHES;
    }

    public function isHashTypeSupported(int $hashType): bool
    {
        return in_array($hashType, $this->getSupportedHashTypes(), true);
    }

    public function getDefaultHashType(): int
    {
        return $this->defaultHashType;
    }

    /**
     * @throws \Ibexa\Core\Repository\User\Exception\PasswordHashTypeNotCompiled
     * @throws \Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType
     */
    public function createPasswordHash(
        #[\SensitiveParameter]
        string $password,
        ?int $hashType = null
    ): string {
        $hashType = $hashType ?? $this->defaultHashType;

        switch ($hashType) {
            case User::PASSWORD_HASH_BCRYPT:
                return password_hash($password, PASSWORD_BCRYPT);

            case User::PASSWORD_HASH_PHP_DEFAULT:
                return password_hash($password, PASSWORD_DEFAULT);

            case User::PASSWORD_HASH_INVALID:
                return '';

            case User::PASSWORD_HASH_ARGON2I:
                if (!defined('PASSWORD_ARGON2I')) {
                    throw new PasswordHashTypeNotCompiled('PASSWORD_ARGON2I');
                }

                return password_hash($password, PASSWORD_ARGON2I);

            case User::PASSWORD_HASH_ARGON2ID:
                if (!defined('PASSWORD_ARGON2ID')) {
                    throw new PasswordHashTypeNotCompiled('PASSWORD_ARGON2ID');
                }

                return password_hash($password, PASSWORD_ARGON2ID);

            default:
                throw new UnsupportedPasswordHashType($hashType);
        }
    }

    public function isValidPassword(
        #[\SensitiveParameter]
        string $plainPassword,
        #[\SensitiveParameter]
        string $passwordHash,
        ?int $hashType = null
    ): bool {
        if (
            $hashType === User::PASSWORD_HASH_BCRYPT
            || $hashType === User::PASSWORD_HASH_PHP_DEFAULT
            || $hashType === User::PASSWORD_HASH_ARGON2I
            || $hashType === User::PASSWORD_HASH_ARGON2ID
            || $hashType === User::PASSWORD_HASH_INVALID
        ) {
            // Let PHP's password functionality do its magic
            return password_verify($plainPassword, $passwordHash);
        }

        return $passwordHash === $this->createPasswordHash($plainPassword, $hashType);
    }

    public function updatePasswordHashTypeOnChange(): bool
    {
        return $this->configResolver->getParameter('password_hash.update_type_on_change');
    }

    public function updatePasswordHashTypeOnLogin(): bool
    {
        return $this->configResolver->getParameter('password_hash.update_type_on_login');
    }
}
