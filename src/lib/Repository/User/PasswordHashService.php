<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\User;

use Ibexa\Contracts\Core\Repository\PasswordHashService as PasswordHashServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Core\Repository\User\Exception\PasswordHashTypeNotCompiled;
use Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType;

/**
 * @internal
 */
final class PasswordHashService implements PasswordHashServiceInterface
{
    private int $defaultHashType;

    private bool $updateTypeOnChange;

    public function __construct(
        int $defaultHashType = User::PASSWORD_HASH_PHP_DEFAULT,
        bool $updateTypeOnChange = false
    ) {
        $this->defaultHashType = $defaultHashType;
        $this->updateTypeOnChange = $updateTypeOnChange;
    }

    public function setDefaultHashType(int $defaultHashType): void
    {
        $this->defaultHashType = $defaultHashType;
    }

    public function setUpdateTypeOnChange(bool $updateTypeOnChange): void
    {
        $this->updateTypeOnChange = $updateTypeOnChange;
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

    public function createPasswordHash(
        #[\SensitiveParameter]
        string $plainPassword,
        ?int $hashType = null
    ): string {
        $hashType = $hashType ?? $this->getDefaultHashType();

        switch ($hashType) {
            case User::PASSWORD_HASH_BCRYPT:
                return password_hash($plainPassword, PASSWORD_BCRYPT);

            case User::PASSWORD_HASH_PHP_DEFAULT:
                return password_hash($plainPassword, PASSWORD_DEFAULT);

            case User::PASSWORD_HASH_INVALID:
                return '';

            case User::PASSWORD_HASH_ARGON2I:
                if (!defined('PASSWORD_ARGON2I')) {
                    throw new PasswordHashTypeNotCompiled('PASSWORD_ARGON2I');
                }

                return password_hash($plainPassword, PASSWORD_ARGON2I);

            case User::PASSWORD_HASH_ARGON2ID:
                if (!defined('PASSWORD_ARGON2ID')) {
                    throw new PasswordHashTypeNotCompiled('PASSWORD_ARGON2ID');
                }

                return password_hash($plainPassword, PASSWORD_ARGON2ID);

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

        try {
            return $passwordHash === $this->createPasswordHash($plainPassword, $hashType);
        } catch (PasswordHashTypeNotCompiled|UnsupportedPasswordHashType $e) {
            // If the hash type is not compiled or unsupported we can't verify the password so it's not valid
            return false;
        }
    }

    public function shouldPasswordHashTypeBeUpdatedOnChange(): bool
    {
        return $this->updateTypeOnChange;
    }
}
