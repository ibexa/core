<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

interface PasswordHashService
{
    /**
     * Sets the default password hash type.
     *
     * @param int $defaultHashType The default password hash type, one of Ibexa\Contracts\Core\Repository\Values\User\User::SUPPORTED_PASSWORD_HASHES.
     */
    public function setDefaultHashType(int $defaultHashType): void;

    /**
     * Sets whether the password hash type should be updated when the password is changed.
     *
     * @param bool $updateTypeOnChange Whether to update the password hash type on change.
     */
    public function setUpdateTypeOnChange(bool $updateTypeOnChange): void;

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
     * @throws \Ibexa\Core\Repository\User\Exception\PasswordHashTypeNotCompiled
     * @throws \Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType
     */
    public function createPasswordHash(string $plainPassword, ?int $hashType = null): string;

    /**
     * Validates given $plainPassword against $passwordHash.
     *
     * If non-provided, the default password hash type will be used.
     */
    public function isValidPassword(string $plainPassword, string $passwordHash, ?int $hashType = null): bool;

    public function shouldPasswordHashTypeBeUpdatedOnChange(): bool;
}
