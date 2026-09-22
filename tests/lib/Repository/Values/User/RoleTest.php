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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Role::class)]
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
     */
    public function testUnsetProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $role = new Role(['id' => 1]);
        unset($role->id);
        self::fail('Unsetting read-only property succeeded');
    }
}
