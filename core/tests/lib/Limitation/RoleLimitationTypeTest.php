<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandlerInterface;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandlerInterface;
use Ibexa\Contracts\Core\Persistence\User\Handler as UserHandlerInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\UserRoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\RoleLimitationType;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\User\Role;
use Ibexa\Core\Repository\Values\User\User;
use Ibexa\Core\Repository\Values\User\UserGroup;
use Ibexa\Core\Repository\Values\User\UserGroupRoleAssignment;
use Ibexa\Core\Repository\Values\User\UserRoleAssignment;

final class RoleLimitationTypeTest extends Base
{
    private RoleLimitationType $limitationType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->limitationType = new RoleLimitationType(
            $this->getPersistenceMock()
        );
    }

    /**
     * @dataProvider providerForTestAcceptValue
     */
    public function testAcceptValue(UserRoleLimitation $limitation): void
    {
        $this->expectNotToPerformAssertions();
        $this->limitationType->acceptValue($limitation);
    }

    public function providerForTestAcceptValue(): array
    {
        return [
            [
                new UserRoleLimitation([
                    'limitationValues' => [],
                ]),
            ],
            [
                new UserRoleLimitation([
                    'limitationValues' => [4, 8],
                ]),
            ],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValueException
     */
    public function testAcceptValueException(UserRoleLimitation $limitation): void
    {
        $this->expectException(InvalidArgumentType::class);
        $this->limitationType->acceptValue($limitation);
    }

    public function providerForTestAcceptValueException(): array
    {
        return [
            [
                new UserRoleLimitation([
                    'limitationValues' => 1,
                ]),
            ],
            [
                new UserRoleLimitation([
                    'limitationValues' => null,
                ]),
            ],
            [
                new UserRoleLimitation([
                    'limitationValues' => 'string',
                ]),
            ],
            [
                new UserRoleLimitation([
                    'limitationValues' => ['string'],
                ]),
            ],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValue
     */
    public function testValidatePass(UserRoleLimitation $limitation): void
    {
        $userHandlerMock = $this->createMock(UserHandlerInterface::class);
        $contentHandlerMock = $this->createMock(ContentHandlerInterface::class);

        if ($limitation->limitationValues !== null) {
            $userHandlerMock
                ->method('loadRole')
                ->withConsecutive([4, Role::STATUS_DEFINED], [8, Role::STATUS_DEFINED]);

            $this->getPersistenceMock()
                ->method('userHandler')
                ->willReturn($userHandlerMock);
        }

        if ($limitation->limitationValues !== null) {
            $contentHandlerMock
                ->method('loadContentInfo')
                ->withConsecutive([14], [21]);

            $this->getPersistenceMock()
                ->method('contentHandler')
                ->willReturn($contentHandlerMock);
        }

        $validationErrors = $this->limitationType->validate($limitation);

        self::assertEmpty($validationErrors);
    }

    /**
     * @dataProvider providerForTestValidateError
     */
    public function testValidateError(UserRoleLimitation $limitation, int $errorCount): void
    {
        $userHandlerMock = $this->createMock(UserHandlerInterface::class);

        if ($limitation->limitationValues !== null) {
            $userHandlerMock
                ->method('loadRole')
                ->withConsecutive([4, Role::STATUS_DEFINED], [8, Role::STATUS_DEFINED])
                ->willReturnOnConsecutiveCalls(
                    self::throwException(new NotFoundException('Role', 4)),
                    new Role()
                );

            $this->getPersistenceMock()
                ->method('userHandler')
                ->willReturn($userHandlerMock);
        }

        $validationErrors = $this->limitationType->validate($limitation);
        self::assertCount($errorCount, $validationErrors);
    }

    public function providerForTestValidateError()
    {
        return [
            [
                new UserRoleLimitation([
                    'limitationValues' => [4, 8],
                ]),
                1,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestEvaluate
     */
    public function testEvaluate(
        UserRoleLimitation $limitation,
        ValueObject $object,
        ?array $targets,
        ?bool $expected
    ): void {
        $locationHandlerMock = $this->createMock(LocationHandlerInterface::class);

        if ($object instanceof UserRoleAssignment) {
            $locationHandlerMock
                ->method('loadLocationsByContent')
                ->with($object instanceof User ? $object->getUserId() : $object->getUser()->getUserId())
                ->willReturn([
                    new Location(['contentId' => 14]),
                    new Location(['contentId' => 25]),
                ]);

            $this->getPersistenceMock()
                ->method('locationHandler')
                ->willReturn($locationHandlerMock);
        }

        $value = (new RoleLimitationType($this->getPersistenceMock()))->evaluate(
            $limitation,
            $this->getUserMock(),
            $object,
            $targets
        );

        self::assertEquals($expected, $value);
    }

    public function providerForTestEvaluate()
    {
        return [
            'valid_role_limitation' => [
                'limitation' => new UserRoleLimitation([
                    'limitationValues' => [4, 8],
                ]),
                'object' => new Role(['id' => 4]),
                'targets' => null,
                'expected' => true,
            ],
            'allow_non_role_limitation' => [
                'limitation' => new UserRoleLimitation([
                    'limitationValues' => [],
                ]),
                'object' => new Role(['id' => 4]),
                'targets' => null,
                'expected' => false,
            ],
            'pass_to_next_limitation' => [
                'limitation' => new UserRoleLimitation([
                    'limitationValues' => [4, 8],
                ]),
                'object' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => 14,
                    ]),
                ]),
                'targets' => null,
                'expected' => null,
            ],
            'user_role_assigment_valid' => [
                'limitation' => new UserRoleLimitation([
                    'limitationValues' => [4, 8],
                ]),
                'object' => new UserRoleAssignment([
                    'user' => new User([
                        'content' => new Content([
                            'versionInfo' => new VersionInfo([
                                'contentInfo' => new ContentInfo([
                                    'id' => 66,
                                ]),
                            ]),
                        ]),
                    ]),
                    'role' => new Role(['id' => 4]),
                ]),
                'targets' => null,
                'expected' => true,
            ],
            'user_role_assigment_invalid_role' => [
                'limitation' => new UserRoleLimitation([
                    'limitationValues' => [4, 8],
                ]),
                'object' => new UserRoleAssignment([
                    'user' => new User([
                        'content' => new Content([
                            'versionInfo' => new VersionInfo([
                                'contentInfo' => new ContentInfo([
                                    'id' => 66,
                                ]),
                            ]),
                        ]),
                    ]),
                    'role' => new Role(['id' => 7]),
                ]),
                'targets' => null,
                'expected' => false,
            ],
            'user_group_role_assigment_valid' => [
                'limitation' => new UserRoleLimitation([
                    'limitationValues' => [4, 8],
                ]),
                'object' => new UserGroupRoleAssignment([
                    'userGroup' => new UserGroup([
                        'content' => new Content([
                            'versionInfo' => new VersionInfo([
                                'contentInfo' => new ContentInfo([
                                    'id' => 66,
                                ]),
                            ]),
                        ]),
                    ]),
                    'role' => new Role(['id' => 4]),
                ]),
                'targets' => null,
                'expected' => true,
            ],
            'user_group_role_assigment_invalid_role' => [
                'limitation' => new UserRoleLimitation([
                    'limitationValues' => [4, 8],
                ]),
                'object' => new UserGroupRoleAssignment([
                    'userGroup' => new UserGroup([
                        'content' => new Content([
                            'versionInfo' => new VersionInfo([
                                'contentInfo' => new ContentInfo([
                                    'id' => 66,
                                ]),
                            ]),
                        ]),
                    ]),
                    'role' => new Role(['id' => 7]),
                ]),
                'targets' => null,
                'expected' => false,
            ],
        ];
    }
}
