<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\User;

use DateTimeImmutable;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the User field type.
 */
class Value extends BaseValue
{
    /**
     * Has stored login.
     */
    public bool $hasStoredLogin;

    public int $contentId;

    public string $login;

    /**
     * Email.
     */
    public string $email;

    public string $passwordHash;

    public int $passwordHashType;

    public ?DateTimeImmutable $passwordUpdatedAt;

    public bool $enabled;

    /**
     * Max login.
     */
    public int $maxLogin;

    /**
     * @var string Write-only property, takes a plain password for use when creating user or updating password.
     */
    public string $plainPassword;

    public function __toString(): string
    {
        return $this->login;
    }
}
