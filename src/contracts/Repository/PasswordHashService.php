<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Core\Repository\User\Exception\PasswordHashTypeNotCompiled;
use Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType;

interface PasswordHashService
{
    /**
     * Returns default password hash type.
     *
     * @return int
     */
    public function getDefaultHashType(): int;

    /**
     * Returns list of supported password hash types.
     *
     * @return int[]
     */
    public function getSupportedHashTypes(): array;

    /**
     * Shortcut method to check if given hash type is supported.
     */
    public function isHashTypeSupported(int $hashType): bool;

    /**
     * Create hash from given plain password.
     *
     * If non-provided, the default password hash type will be used.
     *
     * @throws PasswordHashTypeNotCompiled
     * @throws UnsupportedPasswordHashType
     */
    public function createPasswordHash(string $plainPassword, ?int $hashType = null): string;

    /**
     * Validates given $plainPassword against $passwordHash.
     *
     * If non-provided, the default password hash type will be used.
     */
    public function isValidPassword(string $plainPassword, string $passwordHash, ?int $hashType = null): bool;

    /**
     * Returns true if password hash type should be updated when the user changes password.
     *
     * @return bool
     */
    public function updatePasswordHashTypeOnChange(): bool;

    /**
     * Returns true if password hash type should be updated when the user logs in.
     *
     * @return bool
     */
    public function updatePasswordHashTypeOnLogin(): bool;

    /**
     * Returns true if the password hash needs to be rehashed.
     *
     * This is used to determine if the password hash should be updated when the user logs in.
     * It will return true if the hash type of the existing password hash does not match the provided hash type,
     * or if the defaults for PHP's password hashing options have changed (e.g., cost factor).
     *
     * @param string $passwordHash The existing password hash
     * @param int $hashType The hash type to check against
     *
     * @return bool
     */
    public function passwordNeedsRehash(string $passwordHash, int $hashType): bool;
}
