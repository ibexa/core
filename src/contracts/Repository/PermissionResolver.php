<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\User\LookupLimitationResult;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;

/**
 * This service provides methods for resolving permissions.
 */
interface PermissionResolver
{
    /**
     * Get current user reference.
     */
    public function getCurrentUserReference(): UserReference;

    /**
     * Sets the current user to the given $user.
     */
    public function setCurrentUserReference(UserReference $userReference): void;

    /**
     * Low level permission function: Returns boolean value, or an array of limitations that user permission depends on.
     *
     * Note: boolean value describes full access (true) or no access at all (false), array can be seen as a maybe..
     *
     * WARNING: This is a low level method, if possible strongly prefer to use canUser() as it is able to handle limitations.
     *          This includes Role Assignment limitations, but also future policy limitations added in kernel,
     *          or as plain user configuration and/or extending the system.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If module or function is invalid.
     *
     * @param string $module The module, aka controller identifier to check permissions on
     * @param string $function The function, aka the controller action to check permissions on
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference|null $userReference User for
     *        which the information is returned, current user will be used if null
     *
     * @return bool|array if limitations are on this function an array of limitations is returned
     *
     * @phpstan-return bool|array<
     *     array{
     *         limitation: \Ibexa\Contracts\Core\Repository\Values\User\Limitation|null,
     *         policies: array<\Ibexa\Contracts\Core\Repository\Values\User\Policy>
     *     },
     * >
     */
    public function hasAccess(string $module, string $function, ?UserReference $userReference = null);

    /**
     * Indicates if the current user is allowed to perform an action given by the function on the given
     * objects.
     *
     * Example: canUser( 'content', 'edit', $content, [$location] );
     *          This will check edit permission on content given the specific location, if skipped if will check on all
     *          locations.
     *
     * Example2: canUser( 'section', 'assign', $content, [$section] );
     *           Check if user has access to assign $content to $section.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If any of the arguments are invalid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If value of the LimitationValue is unsupported
     *
     * @param string $module The module, aka controller identifier to check permissions on
     * @param string $function The function, aka the controller action to check permissions on
     * @param object $object The object to check if the user has access to
     * @param object[] $targets An array of location, parent or "assignment" value objects
     */
    public function canUser(string $module, string $function, object $object, array $targets = []): bool;

    /**
     * @param string $module The module, aka controller identifier to check permissions on
     * @param string $function The function, aka the controller action to check permissions on
     * @param object $object The object to check if the user has access to
     * @param object[] $targets An array of location, parent or "assignment" value objects
     * @param string[] $limitationsIdentifiers An array of Limitations identifiers to filter from all which will pass
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\LookupLimitationResult
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function lookupLimitations(
        string $module,
        string $function,
        object $object,
        array $targets = [],
        array $limitationsIdentifiers = []
    ): LookupLimitationResult;
}
