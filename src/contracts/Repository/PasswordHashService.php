<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

interface PasswordHashService
{
    /**
     * Sets the ConfigResolver instance.
     *
     * @param \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface $configResolver
     */
    public function setConfigResolver(ConfigResolverInterface $configResolver): void;

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

    /**
     * Returns true if password hash type should be updated when the user changes password.
     *
     * @return bool
     */
    public function updatePasswordHashTypeOnChange(): bool;
}
