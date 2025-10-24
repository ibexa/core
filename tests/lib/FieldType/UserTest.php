<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\User;
use Ibexa\Contracts\Core\Repository\PasswordHashService;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\User\User as UserAlias;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\User\Type;
use Ibexa\Core\FieldType\User\Type as UserType;
use Ibexa\Core\FieldType\User\Value;
use Ibexa\Core\FieldType\User\Value as UserValue;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Persistence\Cache\UserHandler;
use Ibexa\Core\Repository\User\PasswordValidatorInterface;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition as CoreFieldDefinition;
use Ibexa\Core\Repository\Values\User\User as RepositoryUser;
use Ibexa\Tests\Core\FieldType\DataProvider\UserValidatorConfigurationSchemaProvider;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;

/**
 * @group fieldType
 * @group ibexa_url
 */
class UserTest extends FieldTypeTestCase
{
    private const int UNSUPPORTED_HASH_TYPE = 0xDEADBEEF;

    protected function createFieldTypeUnderTest(): UserType
    {
        $fieldType = new UserType(
            $this->createMock(UserHandler::class),
            $this->createMock(PasswordHashService::class),
            $this->createMock(PasswordValidatorInterface::class)
        );
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return (new UserValidatorConfigurationSchemaProvider())
            ->getExpectedValidatorConfigurationSchema();
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [
            UserType::PASSWORD_TTL_SETTING => [
                'type' => 'int',
                'default' => null,
            ],
            UserType::PASSWORD_TTL_WARNING_SETTING => [
                'type' => 'int',
                'default' => null,
            ],
            UserType::REQUIRE_UNIQUE_EMAIL => [
                'type' => 'bool',
                'default' => true,
            ],
            UserType::USERNAME_PATTERN => [
                'type' => 'string',
                'default' => '^[^@]+$',
            ],
        ];
    }

    protected function getEmptyValueExpectation(): UserValue
    {
        return new UserValue();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        yield [
            23,
            InvalidArgumentException::class,
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new UserValue(),
        ];

        yield 'empty array' => [
            [],
            new UserValue([]),
        ];

        yield 'user value with login' => [
            new UserValue(['login' => 'sindelfingen']),
            new UserValue(['login' => 'sindelfingen']),
        ];

        yield 'array with user data' => [
            $userData = [
                'hasStoredLogin' => true,
                'contentId' => 23,
                'login' => 'sindelfingen',
                'email' => 'sindelfingen@example.com',
                'passwordHash' => '1234567890abcdef',
                'passwordHashType' => 'md5',
                'enabled' => true,
                'maxLogin' => 1000,
            ],
            new UserValue($userData),
        ];

        yield 'user value with full data' => [
            new UserValue(
                $userData = [
                    'hasStoredLogin' => true,
                    'contentId' => 23,
                    'login' => 'sindelfingen',
                    'email' => 'sindelfingen@example.com',
                    'passwordHash' => '1234567890abcdef',
                    'passwordHashType' => 'md5',
                    'enabled' => true,
                    'maxLogin' => 1000,
                ]
            ),
            new UserValue($userData),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        $passwordUpdatedAt = new DateTimeImmutable();

        return [
            [
                new UserValue(),
                null,
            ],
            [
                new UserValue(
                    $userData = [
                        'hasStoredLogin' => true,
                        'contentId' => 23,
                        'login' => 'sindelfingen',
                        'email' => 'sindelfingen@example.com',
                        'passwordHash' => '1234567890abcdef',
                        'passwordHashType' => 'md5',
                        'passwordUpdatedAt' => $passwordUpdatedAt,
                        'enabled' => true,
                        'maxLogin' => 1000,
                        'plainPassword' => null,
                    ]
                ),
                [
                    'passwordUpdatedAt' => $passwordUpdatedAt->getTimestamp(),
                ] + $userData,
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        yield [
            null,
            new UserValue(),
        ];

        yield [
            $userData = [
                'hasStoredLogin' => true,
                'contentId' => 23,
                'login' => 'sindelfingen',
                'email' => 'sindelfingen@example.com',
                'passwordHash' => '1234567890abcdef',
                'passwordHashType' => 'md5',
                'passwordUpdatedAt' => 1567071092,
                'enabled' => true,
                'maxLogin' => 1000,
            ],
            new UserValue([
                'passwordUpdatedAt' => new DateTimeImmutable('@1567071092'),
            ] + $userData),
        ];
    }

    public function provideValidDataForValidate(): iterable
    {
        yield from [];
    }

    public function provideInvalidDataForValidate(): iterable
    {
        yield from [];
    }

    /**
     * @covers \Ibexa\Core\FieldType\User\Type::validate
     *
     * @dataProvider providerForTestValidate
     *
     * @param Value $userValue
     * @param array $expectedValidationErrors
     * @param callable|null $loadByLoginBehaviorCallback
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testValidate(
        UserValue $userValue,
        array $expectedValidationErrors,
        ?callable $loadByLoginBehaviorCallback
    ): void {
        $userHandlerMock = $this->createMock(UserHandler::class);

        if (null !== $loadByLoginBehaviorCallback) {
            $loadByLoginBehaviorCallback(
                $userHandlerMock
                    ->expects(self::once())
                    ->method('loadByLogin')
                    ->with($userValue->login)
            );
        }

        $userType = new UserType(
            $userHandlerMock,
            $this->createMock(PasswordHashService::class),
            $this->createMock(PasswordValidatorInterface::class)
        );

        $fieldSettings = [
            Type::USERNAME_PATTERN => '.*',
            Type::REQUIRE_UNIQUE_EMAIL => false,
        ];

        $fieldDefinitionMock = $this->createMock(FieldDefinition::class);
        $fieldDefinitionMock->method('__get')->with('fieldSettings')->willReturn($fieldSettings);
        $fieldDefinitionMock->method('getFieldSettings')->willReturn($fieldSettings);

        $validationErrors = $userType->validate($fieldDefinitionMock, $userValue);

        self::assertEquals($expectedValidationErrors, $validationErrors);
    }

    public function testInvalidLoginFormat(): void
    {
        $validateUserValue = new UserValue([
            'hasStoredLogin' => false,
            'contentId' => 46,
            'login' => 'validate@user',
            'email' => 'example@test.ibexa.co',
            'passwordHash' => '1234567890abcdef',
            'passwordHashType' => 'md5',
            'enabled' => true,
            'maxLogin' => 1000,
            'plainPassword' => 'testPassword',
        ]);

        $validationErrors = $this->mockValidationErrors($validateUserValue);

        self::assertEquals([
            new ValidationError(
                'Invalid login format',
                null,
                [],
                'username'
            ),
        ], $validationErrors);
    }

    public function testValidLoginFormat(): void
    {
        $validateUserValue = new UserValue([
            'hasStoredLogin' => false,
            'contentId' => 46,
            'login' => 'validate_user',
            'email' => 'example@test.ibexa.co',
            'passwordHash' => '1234567890abcdef',
            'passwordHashType' => 'md5',
            'enabled' => true,
            'maxLogin' => 1000,
            'plainPassword' => 'testPassword',
        ]);

        $validationErrors = $this->mockValidationErrors($validateUserValue);

        self::assertEquals([], $validationErrors);
    }

    public function testEmailAlreadyTaken(): void
    {
        $existingUser = new User([
            'id' => 23,
            'login' => 'existing_user',
            'email' => 'test@test.ibexa.co',
        ]);

        $validateUserValue = new UserValue([
            'hasStoredLogin' => false,
            'contentId' => 46,
            'login' => 'validate_user',
            'email' => 'test@test.ibexa.co',
            'passwordHash' => '1234567890abcdef',
            'passwordHashType' => 'md5',
            'enabled' => true,
            'maxLogin' => 1000,
            'plainPassword' => 'testPassword',
        ]);

        $userHandlerMock = $this->createMock(UserHandler::class);

        $userHandlerMock
            ->expects(self::once())
            ->method('loadByLogin')
            ->with($validateUserValue->login)
            ->willThrowException(new NotFoundException('', ''));

        $userHandlerMock
            ->expects(self::once())
            ->method('loadByEmail')
            ->with($validateUserValue->email)
            ->willReturn($existingUser);

        $userType = new UserType(
            $userHandlerMock,
            $this->createMock(PasswordHashService::class),
            $this->createMock(PasswordValidatorInterface::class)
        );

        $fieldSettings = [
            UserType::REQUIRE_UNIQUE_EMAIL => true,
            UserType::USERNAME_PATTERN => '^[^@]+$',
        ];

        $fieldDefinition = new CoreFieldDefinition(['fieldSettings' => $fieldSettings]);

        $validationErrors = $userType->validate($fieldDefinition, $validateUserValue);

        self::assertEquals([
            new ValidationError(
                "Email '%email%' is used by another user. You must enter a unique email.",
                null,
                [
                    '%email%' => $validateUserValue->email,
                ],
                'email'
            ),
        ], $validationErrors);
    }

    /**
     * @covers \Ibexa\Core\FieldType\User\Type::toPersistenceValue
     *
     * @dataProvider providerForTestCreatePersistenceValue
     */
    public function testCreatePersistenceValue(
        array $userValueDate,
        array $expectedFieldValueExternalData
    ): void {
        $passwordHashServiceMock = $this->createMock(PasswordHashService::class);
        $passwordHashServiceMock->method('getDefaultHashType')->willReturn(RepositoryUser::DEFAULT_PASSWORD_HASH);
        $userType = new UserType(
            $this->createMock(UserHandler::class),
            $passwordHashServiceMock,
            $this->createMock(PasswordValidatorInterface::class)
        );

        $value = new UserValue($userValueDate);
        $fieldValue = $userType->toPersistenceValue($value);

        $expected = new FieldValue(
            [
                'data' => null,
                'externalData' => $expectedFieldValueExternalData,
                'sortKey' => null,
            ]
        );
        self::assertEquals($expected, $fieldValue);
    }

    public function providerForTestCreatePersistenceValue(): iterable
    {
        $passwordUpdatedAt = new DateTimeImmutable();
        $userData = [
            'hasStoredLogin' => false,
            'contentId' => 46,
            'login' => 'validate_user',
            'email' => 'test@test.ibexa.co',
            'passwordHash' => '1234567890abcdef',
            'enabled' => true,
            'maxLogin' => 1000,
            'plainPassword' => '',
            'passwordUpdatedAt' => $passwordUpdatedAt,
        ];

        yield 'when password hash type is given' => [
            [
                'passwordHashType' => UserAlias::PASSWORD_HASH_PHP_DEFAULT,
            ] + $userData,
            [
                'passwordHashType' => UserAlias::PASSWORD_HASH_PHP_DEFAULT,
                'passwordUpdatedAt' => $passwordUpdatedAt->getTimestamp(),
            ] + $userData,
        ];
        yield 'when password hash type is null' => [
            [
                'passwordHashType' => null,
            ] + $userData,
            [
                'passwordHashType' => UserAlias::DEFAULT_PASSWORD_HASH,
                'passwordUpdatedAt' => $passwordUpdatedAt->getTimestamp(),
            ] + $userData,
        ];
        yield 'when password hash type is unsupported' => [
            [
                'passwordHashType' => self::UNSUPPORTED_HASH_TYPE,
            ] + $userData,
            [
                'passwordHashType' => UserAlias::DEFAULT_PASSWORD_HASH,
                'passwordUpdatedAt' => $passwordUpdatedAt->getTimestamp(),
            ] + $userData,
        ];
    }

    public function testEmailFreeToUse(): void
    {
        $validateUserValue = new UserValue([
            'hasStoredLogin' => false,
            'contentId' => 46,
            'login' => 'validate_user',
            'email' => 'test@test.ibexa.co',
            'passwordHash' => '1234567890abcdef',
            'passwordHashType' => 'md5',
            'enabled' => true,
            'maxLogin' => 1000,
            'plainPassword' => 'testPassword',
        ]);

        $userHandlerMock = $this->createMock(UserHandler::class);

        $userHandlerMock
            ->expects(self::once())
            ->method('loadByLogin')
            ->with($validateUserValue->login)
            ->willThrowException(new NotFoundException('', ''));

        $userHandlerMock
            ->expects(self::once())
            ->method('loadByEmail')
            ->with($validateUserValue->email)
            ->willThrowException(new NotFoundException('', ''));

        $userType = new UserType(
            $userHandlerMock,
            $this->createMock(PasswordHashService::class),
            $this->createMock(PasswordValidatorInterface::class)
        );

        $fieldSettings = [
            UserType::REQUIRE_UNIQUE_EMAIL => true,
            UserType::USERNAME_PATTERN => '^[^@]+$',
        ];

        $fieldDefinition = new CoreFieldDefinition(['fieldSettings' => $fieldSettings]);

        $validationErrors = $userType->validate($fieldDefinition, $validateUserValue);

        self::assertEquals([], $validationErrors);
    }

    /**
     * Data provider for testValidate test.
     *
     * @see testValidate
     *
     * @return array data sets for testValidate method (<code>$userValue, $expectedValidationErrors, $loadByLoginBehaviorCallback</code>)
     */
    public function providerForTestValidate(): array
    {
        return [
            [
                new UserValue(
                    [
                        'hasStoredLogin' => false,
                        'contentId' => 23,
                        'login' => 'user',
                        'email' => 'invalid',
                        'passwordHash' => '1234567890abcdef',
                        'passwordHashType' => 'md5',
                        'enabled' => true,
                        'maxLogin' => 1000,
                        'plainPassword' => 'testPassword',
                    ]
                ),
                [
                    new ValidationError(
                        "The given e-mail '%email%' is invalid",
                        null,
                        [
                            '%email%' => 'invalid',
                        ],
                        'email'
                    ),
                ],
                static function (InvocationMocker $loadByLoginInvocationMocker) {
                    $loadByLoginInvocationMocker->willThrowException(
                        new NotFoundException('user', 'user')
                    );
                },
            ],
            [
                new UserValue([
                    'hasStoredLogin' => false,
                    'contentId' => 23,
                    'login' => 'sindelfingen',
                    'email' => 'sindelfingen@example.com',
                    'passwordHash' => '1234567890abcdef',
                    'passwordHashType' => 'md5',
                    'enabled' => true,
                    'maxLogin' => 1000,
                    'plainPassword' => 'testPassword',
                ]),
                [
                    new ValidationError(
                        "The user login '%login%' is used by another user. You must enter a unique login.",
                        null,
                        [
                            '%login%' => 'sindelfingen',
                        ],
                        'username'
                    ),
                ],
                function (InvocationMocker $loadByLoginInvocationMocker) {
                    $loadByLoginInvocationMocker->willReturn(
                        $this->createMock(UserValue::class)
                    );
                },
            ],
            [
                new UserValue([
                    'hasStoredLogin' => true,
                    'contentId' => 23,
                    'login' => 'sindelfingen',
                    'email' => 'sindelfingen@example.com',
                    'passwordHash' => '1234567890abcdef',
                    'passwordHashType' => 'md5',
                    'enabled' => true,
                    'maxLogin' => 1000,
                ]),
                [],
                null,
            ],
        ];
    }

    public function provideValidFieldSettings(): iterable
    {
        return [
            [
                [],
            ],
            [
                [
                    UserType::PASSWORD_TTL_SETTING => 30,
                ],
            ],
            [
                [
                    UserType::PASSWORD_TTL_SETTING => 30,
                    UserType::PASSWORD_TTL_WARNING_SETTING => null,
                ],
            ],
            [
                [
                    UserType::PASSWORD_TTL_SETTING => 30,
                    UserType::PASSWORD_TTL_WARNING_SETTING => 14,
                    UserType::REQUIRE_UNIQUE_EMAIL => true,
                    UserType::USERNAME_PATTERN => '^[^!]+$',
                ],
            ],
        ];
    }

    public function provideInValidFieldSettings(): array
    {
        return [
            [
                [
                    UserType::PASSWORD_TTL_WARNING_SETTING => 30,
                ],
            ],
            [
                [
                    UserType::PASSWORD_TTL_SETTING => null,
                    UserType::PASSWORD_TTL_WARNING_SETTING => 60,
                ],
            ],
            [
                [
                    UserType::PASSWORD_TTL_SETTING => 30,
                    UserType::PASSWORD_TTL_WARNING_SETTING => 60,
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_user';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new UserValue(['login' => 'johndoe']), 'johndoe', [], 'en_GB'],
        ];
    }

    /**
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function mockValidationErrors(UserValue $validateUserValue): array
    {
        $userHandlerMock = $this->createMock(UserHandler::class);

        $userHandlerMock
            ->expects(self::once())
            ->method('loadByLogin')
            ->with($validateUserValue->login)
            ->willThrowException(new NotFoundException('', ''));

        $userType = new UserType(
            $userHandlerMock,
            $this->createMock(PasswordHashService::class),
            $this->createMock(PasswordValidatorInterface::class)
        );

        $fieldSettings = [
            UserType::REQUIRE_UNIQUE_EMAIL => false,
            UserType::USERNAME_PATTERN => '^[^@]+$',
        ];

        $fieldDefinition = new CoreFieldDefinition(['fieldSettings' => $fieldSettings]);

        return $userType->validate($fieldDefinition, $validateUserValue);
    }
}
