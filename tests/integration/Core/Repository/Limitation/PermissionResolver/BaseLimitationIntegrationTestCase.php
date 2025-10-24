<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Limitation\PermissionResolver;

use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Base class for all Limitation integration tests.
 */
abstract class BaseLimitationIntegrationTestCase extends BaseTestCase
{
    /** @var PermissionResolver */
    protected $permissionResolver;

    protected function setUp(): void
    {
        $repository = $this->getRepository(false);
        $this->permissionResolver = $repository->getPermissionResolver();
    }

    /**
     * Map Limitations list to readable string for debugging purposes.
     *
     * @param Limitation[] $limitations
     *
     * @return string
     */
    protected function getLimitationsListAsString(array $limitations): string
    {
        $str = '';
        foreach ($limitations as $limitation) {
            $str .= sprintf(
                '%s[%s]',
                get_class($limitation),
                implode(', ', $limitation->limitationValues)
            );
        }

        return $str;
    }

    /**
     * Create Editor user with the given Policy and Limitations and set it as current user.
     *
     * @param string $module
     * @param string $function
     * @param array $limitations
     *
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    protected function loginAsEditorUserWithLimitations(
        string $module,
        string $function,
        array $limitations = []
    ): void {
        $user = $this->createUserWithPolicies(
            uniqid('editor'),
            [
                ['module' => $module, 'function' => $function, 'limitations' => $limitations],
            ]
        );

        $this->permissionResolver->setCurrentUserReference($user);
    }

    /**
     * @param bool $expectedResult
     * @param string $module
     * @param string $function
     * @param array $limitations
     * @param ValueObject $object
     * @param array $targets
     *
     * @throws BadStateException
     * @throws InvalidArgumentException
     */
    protected function assertCanUser(
        bool $expectedResult,
        string $module,
        string $function,
        array $limitations,
        ValueObject $object,
        array $targets = []
    ): void {
        self::assertEquals(
            $expectedResult,
            $this->permissionResolver->canUser($module, $function, $object, $targets),
            sprintf(
                'Failure for %s/%s with Limitations: %s',
                $module,
                $function,
                $this->getLimitationsListAsString($limitations)
            )
        );
    }
}
