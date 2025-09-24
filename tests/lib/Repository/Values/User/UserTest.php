<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\User\User;
use Ibexa\Tests\Core\Repository\Values\ValueObjectTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Values\User\User
 */
final class UserTest extends TestCase
{
    use ValueObjectTestTrait;

    public function testGetName(): void
    {
        $name = 'Translated name';
        $contentMock = $this->createMock(Content::class);
        $versionInfoMock = $this->createMock(VersionInfo::class);

        $contentMock->expects(self::once())
            ->method('getVersionInfo')
            ->willReturn($versionInfoMock);

        $versionInfoMock->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        $object = new User(['content' => $contentMock]);

        self::assertEquals($name, $object->getName());
    }

    public function testMissingProperty(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $user = new User();
        $value = $user->notDefined;
        self::fail('Succeeded getting non existing property');
    }

    public function testObjectProperties(): void
    {
        $object = new User();
        $properties = $object->attributes();
        self::assertNotContains('internalFields', $properties, 'Internal property found ');
        self::assertContains('id', $properties, 'Property not found ');
        self::assertContains('fields', $properties, 'Property not found ');
        self::assertContains('versionInfo', $properties, 'Property not found ');
        self::assertContains('contentInfo', $properties, 'Property not found ');

        // check for duplicates and double check existence of property
        $propertiesHash = [];
        foreach ($properties as $property) {
            if (isset($propertiesHash[$property])) {
                self::fail("Property '{$property}' exists several times in properties list");
            } elseif (!isset($object->$property)) {
                self::fail("Property '{$property}' does not exist on object, even though it was hinted to be there");
            }
            $propertiesHash[$property] = 1;
        }
    }

    public function testIsPropertySet(): void
    {
        $user = new User();
        $value = isset($user->notDefined);
        self::assertFalse($value);
    }

    public function testUnsetProperty(): void
    {
        $this->expectException(PropertyReadOnlyException::class);

        $user = new User(['login' => 'admin']);
        unset($user->login);
        self::fail('Unsetting read-only property succeeded');
    }
}
