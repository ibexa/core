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
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\MemberOfLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\MemberOfLimitationType;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\User\Role;
use Ibexa\Core\Repository\Values\User\User;
use Ibexa\Core\Repository\Values\User\UserGroup;
use Ibexa\Core\Repository\Values\User\UserGroupRoleAssignment;
use Ibexa\Core\Repository\Values\User\UserRoleAssignment;
use PHPUnit\Framework\Attributes\DataProvider;

final class MemberOfLimitationTypeTest extends Base
{
    private MemberOfLimitationType $limitationType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->limitationType = new MemberOfLimitationType(
            $this->getPersistenceMock()
        );
    }

    #[DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(MemberOfLimitation $limitation): void
    {
        $this->expectNotToPerformAssertions();
        $this->limitationType->acceptValue($limitation);
    }

    public static function providerForTestAcceptValue(): array
    {
        return [
            [
                new MemberOfLimitation([
                    'limitationValues' => [],
                ]),
            ],
            [
                new MemberOfLimitation([
                    'limitationValues' => [MemberOfLimitationType::SELF_USER_GROUP, 8],
                ]),
            ],
        ];
    }

    #[DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(MemberOfLimitation $limitation): void
    {
        $this->expectException(InvalidArgumentType::class);
        $this->limitationType->acceptValue($limitation);
    }

    public static function providerForTestAcceptValueException(): array
    {
        return [
            [
                new MemberOfLimitation([
                    'limitationValues' => 1,
                ]),
            ],
            [
                new MemberOfLimitation([
                    'limitationValues' => null,
                ]),
            ],
            [
                new MemberOfLimitation([
                    'limitationValues' => 'string',
                ]),
            ],
            [
                new MemberOfLimitation([
                    'limitationValues' => ['string'],
                ]),
            ],
        ];
    }

    #[DataProvider('providerForTestAcceptValue')]
    public function testValidatePass(MemberOfLimitation $limitation): void
    {
        $contentHandlerMock = $this->createMock(ContentHandlerInterface::class);

        $contentHandlerMock
            ->method('loadContentInfo')
            ->with(8);

        $this->getPersistenceMock()
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        $validationErrors = $this->limitationType->validate($limitation);

        self::assertEmpty($validationErrors);
    }

    #[DataProvider('providerForTestValidateError')]
    public function testValidateError(MemberOfLimitation $limitation, int $errorCount): void
    {
        $contentHandlerMock = $this->createMock(ContentHandlerInterface::class);

        if ($limitation->limitationValues !== null) {
            $matcher = self::exactly(2);
            $contentHandlerMock->expects($matcher)
                ->method('loadContentInfo')->willReturnCallback(static function (...$parameters) use ($matcher): ?ContentInfo {
                    if ($matcher->numberOfInvocations() === 1) {
                        self::assertSame(14, $parameters[0]);

                        throw new NotFoundException('UserGroup', 18);
                    }
                    if ($matcher->numberOfInvocations() === 2) {
                        self::assertSame(18, $parameters[0]);

                        return new ContentInfo();
                    }

                    return null;
                });

            $this->getPersistenceMock()
                ->method('contentHandler')
                ->willReturn($contentHandlerMock);
        }

        $validationErrors = $this->limitationType->validate($limitation);
        self::assertCount($errorCount, $validationErrors);
    }

    public static function providerForTestValidateError(): array
    {
        return [
            [
                new MemberOfLimitation([
                    'limitationValues' => [14, 18],
                ]),
                1,
            ],
        ];
    }

    #[DataProvider('providerForTestEvaluate')]
    public function testEvaluate(
        MemberOfLimitation $limitation,
        ValueObject $object,
        ?bool $expected
    ): void {
        $locationHandlerMock = $this->createMock(LocationHandlerInterface::class);

        if ($object instanceof User || $object instanceof UserRoleAssignment) {
            $locationHandlerMock
                ->method('loadLocationsByContent')
                ->with($object instanceof User ? $object->getUserId() : $object->getUser()->getUserId())
                ->willReturn([
                    new Location(['parentId' => 13]),
                    new Location(['parentId' => 14]),
                ]);
            $matcher = self::exactly(2);

            $locationHandlerMock->expects($matcher)
                ->method('load')->willReturnCallback(static function (...$parameters) use ($matcher): ?Location {
                    if ($matcher->numberOfInvocations() === 1) {
                        self::assertSame(13, $parameters[0]);

                        return new Location(['contentId' => 14]);
                    }
                    if ($matcher->numberOfInvocations() === 2) {
                        self::assertSame(14, $parameters[0]);

                        return new Location(['contentId' => 25]);
                    }

                    return null;
                });

            $this->getPersistenceMock()
                ->method('locationHandler')
                ->willReturn($locationHandlerMock);
        }

        $value = (new MemberOfLimitationType($this->getPersistenceMock()))->evaluate(
            $limitation,
            $this->getUserMock(),
            $object
        );

        self::assertEquals($expected, $value);
    }

    public static function providerForTestEvaluate(): array
    {
        return [
            'valid_group_limitation' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [14, 18],
                ]),
                'object' => new UserGroup([
                    'content' => new Content([
                        'versionInfo' => new VersionInfo([
                            'contentInfo' => new ContentInfo([
                                'id' => 14,
                            ]),
                        ]),
                    ]),
                ]),
                'expected' => true,
            ],
            'allow_non_user_groups_limitation' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [],
                ]),
                'object' => new UserGroup([
                    'content' => new Content([
                        'versionInfo' => new VersionInfo([
                            'contentInfo' => new ContentInfo([
                                'id' => 14,
                            ]),
                        ]),
                    ]),
                ]),
                'expected' => false,
            ],
            'pass_to_next_limitation' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [14, 18],
                ]),
                'object' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => 14,
                    ]),
                ]),
                'expected' => null,
            ],
            'invalid_user_must_have_permission_to_every_group_user_is_in' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [25, 10],
                ]),
                'object' => new User([
                    'content' => new Content([
                        'versionInfo' => new VersionInfo([
                            'contentInfo' => new ContentInfo([
                                'id' => 66,
                            ]),
                        ]),
                    ]),
                ]),
                'expected' => false,
            ],
            'user_must_have_permission_to_every_group_user_is_in' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [14, 25],
                ]),
                'object' => new User([
                    'content' => new Content([
                        'versionInfo' => new VersionInfo([
                            'contentInfo' => new ContentInfo([
                                'id' => 66,
                            ]),
                        ]),
                    ]),
                ]),
                'expected' => true,
            ],
            'user_role_assigment_valid' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [14, 25],
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
                ]),
                'expected' => true,
            ],
            'user_role_assigment_invalid_user' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [25, 10],
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
                'expected' => false,
            ],
            'user_group_role_assigment_valid' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [14, 18],
                ]),
                'object' => new UserGroupRoleAssignment([
                    'userGroup' => new UserGroup([
                        'content' => new Content([
                            'versionInfo' => new VersionInfo([
                                'contentInfo' => new ContentInfo([
                                    'id' => 14,
                                ]),
                            ]),
                        ]),
                    ]),
                    'role' => new Role(['id' => 7]),
                ]),
                'expected' => true,
            ],
            'user_group_role_assigment_invalid_user_group' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [18],
                ]),
                'object' => new UserGroupRoleAssignment([
                    'userGroup' => new UserGroup([
                        'content' => new Content([
                            'versionInfo' => new VersionInfo([
                                'contentInfo' => new ContentInfo([
                                    'id' => 14,
                                ]),
                            ]),
                        ]),
                    ]),
                    'role' => new Role(['id' => 7]),
                ]),
                'expected' => false,
            ],
        ];
    }

    /**
     * @param \Ibexa\Core\Repository\Values\User\User|\Ibexa\Core\Repository\Values\User\UserRoleAssignment $object
     */
    #[DataProvider('providerForTestEvaluateSelfGroup')]
    public function testEvaluateSelfGroup(
        MemberOfLimitation $limitation,
        ValueObject $object,
        array $currentUserGroupLocations,
        ?bool $expected
    ): void {
        $locationHandlerMock = $this->createMock(LocationHandlerInterface::class);

        $currentUserLocation = [];

        foreach ($currentUserGroupLocations as $groupLocation) {
            $currentUserLocation[] = new Location(['parentId' => $groupLocation->contentId - 1]);
        }
        $matcher = self::exactly(2);
        $locationHandlerMock->expects($matcher)
            ->method('loadLocationsByContent')->willReturnCallback(function (...$parameters) use ($matcher, $object, $currentUserLocation): ?array {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($object instanceof User ? $object->getUserId() : $object->getUser()->getUserId(), $parameters[0]);

                    return [
                        new Location(['parentId' => 13]),
                        new Location(['parentId' => 43]),
                    ];
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->getUserMock()->getUserId(), $parameters[0]);

                    return $currentUserLocation;
                }

                return null;
            });
        $matcher = self::exactly(2 + count($currentUserGroupLocations));

        $locationHandlerMock->expects($matcher)
            ->method('load')->willReturnCallback(static function (...$parameters) use ($matcher, $currentUserGroupLocations): Location {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame(13, $parameters[0]);

                    return new Location(['contentId' => 14]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame(43, $parameters[0]);

                    return new Location(['contentId' => 44]);
                }

                return $currentUserGroupLocations[$matcher->numberOfInvocations() - 3];
            });

        $this->getPersistenceMock()
            ->method('locationHandler')
            ->willReturn($locationHandlerMock);

        $this->getPersistenceMock()
            ->method('locationHandler')
            ->willReturn($locationHandlerMock);

        $value = (new MemberOfLimitationType($this->getPersistenceMock()))->evaluate(
            $limitation,
            $this->getUserMock(),
            $object
        );

        self::assertEquals($expected, $value);
    }

    public static function providerForTestEvaluateSelfGroup(): array
    {
        return [
            'role_assign_to_user_in_same_group' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [MemberOfLimitationType::SELF_USER_GROUP],
                ]),
                'object' => new User([
                    'content' => new Content([
                        'versionInfo' => new VersionInfo([
                            'contentInfo' => new ContentInfo([
                                'id' => 66,
                            ]),
                        ]),
                    ]),
                ]),
                'currentUserGroupLocations' => [
                    new Location(['contentId' => 14]),
                    new Location(['contentId' => 44]),
                    new Location(['contentId' => 55]),
                ],
                'expected' => true,
            ],
            'role_assign_to_user_in_other_group' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [MemberOfLimitationType::SELF_USER_GROUP],
                ]),
                'object' => new User([
                    'content' => new Content([
                        'versionInfo' => new VersionInfo([
                            'contentInfo' => new ContentInfo([
                                'id' => 66,
                            ]),
                        ]),
                    ]),
                ]),
                'currentUserGroupLocations' => [
                    new Location(['contentId' => 11]),
                    new Location(['contentId' => 14]),
                ],
                'expected' => false,
            ],
            'role_assign_to_user_in_overlapped_groups' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [MemberOfLimitationType::SELF_USER_GROUP, 14, 44],
                ]),
                'object' => new User([
                    'content' => new Content([
                        'versionInfo' => new VersionInfo([
                            'contentInfo' => new ContentInfo([
                                'id' => 66,
                            ]),
                        ]),
                    ]),
                ]),
                'currentUserGroupLocations' => [
                    new Location(['contentId' => 1]),
                ],
                'expected' => true,
            ],
            'user_role_assigment_to_user_in_same_group' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [MemberOfLimitationType::SELF_USER_GROUP],
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
                'currentUserGroupLocations' => [
                    new Location(['contentId' => 14]),
                    new Location(['contentId' => 44]),
                    new Location(['contentId' => 55]),
                ],
                'expected' => true,
            ],
            'user_role_assigment_to_user_in_other_group' => [
                'limitation' => new MemberOfLimitation([
                    'limitationValues' => [MemberOfLimitationType::SELF_USER_GROUP],
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
                'currentUserGroupLocations' => [
                    new Location(['contentId' => 11]),
                    new Location(['contentId' => 14]),
                ],
                'expected' => false,
            ],
        ];
    }
}
