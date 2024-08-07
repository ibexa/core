<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;

/**
 * @property-read string $login @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see User::getLogin()} instead.
 * @property-read string $email
 * @property-read string $passwordHash @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see User::getPasswordHash()} instead.
 * @property-read string $hashAlgorithm Hash algorithm used to hash the password
 * @property-read \DateTimeInterface|null $passwordUpdatedAt
 * @property-read bool $enabled User can not login if false
 * @property-read int $maxLogin Max number of time user is allowed to login
 */
abstract class User extends Content implements UserReference
{
    /**
     * @var int[] List of supported (by default) hash types.
     */
    public const SUPPORTED_PASSWORD_HASHES = [
        self::PASSWORD_HASH_BCRYPT,
        self::PASSWORD_HASH_PHP_DEFAULT,
    ];

    /** @var int Passwords in bcrypt */
    public const PASSWORD_HASH_BCRYPT = 6;

    /** @var int Passwords hashed by PHPs default algorithm, which may change over time */
    public const PASSWORD_HASH_PHP_DEFAULT = 7;

    /** @var int Default password hash, used when none is specified, may change over time */
    public const DEFAULT_PASSWORD_HASH = self::PASSWORD_HASH_PHP_DEFAULT;

    /**
     * User login.
     *
     * @var string
     */
    protected $login;

    /**
     * User E-Mail address.
     *
     * @var string
     */
    protected $email;

    /**
     * User password hash.
     *
     * @var string
     */
    protected $passwordHash;

    /**
     * Datetime of last password update.
     *
     * @var \DateTimeInterface|null
     */
    protected $passwordUpdatedAt;

    /**
     * Hash algorithm used to hash the password.
     *
     * @var int
     */
    protected $hashAlgorithm;

    /**
     * Flag to signal if user is enabled or not.
     *
     * User can not login if false
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Max number of time user is allowed to login.
     *
     * @todo: Not used in kernel, should probably be a number of login allowed before changing password.
     *        But new users gets 0 before they activate, admin has 10, and anonymous has 1000 in clean data.
     *
     * @var int
     */
    protected $maxLogin;

    public function getUserId(): int
    {
        // ATM User Id is the same as Content Id
        return $this->getId();
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }
}

class_alias(User::class, 'eZ\Publish\API\Repository\Values\User\User');
