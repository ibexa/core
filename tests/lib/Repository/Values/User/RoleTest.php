<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException;
use Ibexa\Core\Repository\Values\User\Role;
use Ibexa\Tests\Core\Repository\Values\ValueObjectTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Values\User\Role
 */
class RoleTest extends TestCase
{
    use ValueObjectTestTrait;

    /**
     * Test a new class and default values on properties.
     */
    public function testNewClass(): void
    {
        $this->assertPropertiesCorrect(
            [
                'id' => null,
                'identifier' => null,
                'policies' => [],
            ],
            new Role()
        );
    }

    /**
     * Test retrieving missing property.
     *
     * @covers \Ibexa\Core\Repository\Values\User\Role::__get
     */
    public function testMissingProperty(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $role = new Role();
        /** @phpstan-ignore-next-line property.notFound */
        $value = $role->notDefined;
        self::fail('Succeeded getting non existing property');
    }

    /**
     * Test setting read only property.
     *
     * @covers \Ibexa\Core\Repository\Values\User\Role::__set
     */
    public function testReadOnlyProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $role = new Role();
        $role->id = 42;
        self::fail('Succeeded setting read only property');
    }

    /**
     * Test if property exists.
     */
    public function testIsPropertySet(): void
    {
        $role = new Role();
        /** @phpstan-ignore property.notFound */
        $value = isset($role->notDefined);
        self::assertFalse($value);
    }

    /**
     * Test unsetting a property.
     *
     * @covers \Ibexa\Core\Repository\Values\User\Role::__unset
     */
    public function testUnsetProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $role = new Role(['id' => 1]);
        unset($role->id);
        self::fail('Unsetting read-only property succeeded');
    }
}
