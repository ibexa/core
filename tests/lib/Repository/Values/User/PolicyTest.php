<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException;
use Ibexa\Core\Repository\Values\User\Policy;
use Ibexa\Tests\Core\Repository\Values\ValueObjectTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Policy::class)]
class PolicyTest extends TestCase
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
                'roleId' => null,
                'module' => null,
                'function' => null,
                'limitations' => [],
            ],
            new Policy()
        );
    }

    /**
     * Test retrieving missing property.
     */
    public function testMissingProperty(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $policy = new Policy();
        /** @phpstan-ignore property.notFound */
        $value = $policy->notDefined;
        self::fail('Succeeded getting non existing property');
    }

    /**
     * Test setting read only property.
     */
    public function testReadOnlyProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $policy = new Policy();
        $policy->id = 42;
        self::fail('Succeeded setting read only property');
    }

    /**
     * Test if property exists.
     */
    public function testIsPropertySet(): void
    {
        $policy = new Policy();
        /** @phpstan-ignore property.notFound */
        $value = isset($policy->notDefined);
        self::assertFalse($value);

        $value = isset($policy->id);
        self::assertTrue($value);
    }

    /**
     * Test unsetting a property.
     */
    public function testUnsetProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $policy = new Policy(['id' => 1]);
        unset($policy->id);
        self::fail('Unsetting read-only property succeeded');
    }
}
