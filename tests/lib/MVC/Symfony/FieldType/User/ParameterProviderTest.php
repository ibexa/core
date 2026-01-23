<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\FieldType\User;

use DateInterval;
use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\User\PasswordInfo;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Core\FieldType\User\Value;
use Ibexa\Core\MVC\Symfony\FieldType\User\ParameterProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParameterProviderTest extends TestCase
{
    private const EXAMPLE_USER_ID = 1;

    /** @var UserService|MockObject */
    private $userService;

    /** @var User|MockObject */
    private $user;

    /** @var ParameterProvider */
    private $parameterProvider;

    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);

        $this->userService = $this->createMock(UserService::class);
        $this->userService
            ->method('loadUser')
            ->with(self::EXAMPLE_USER_ID, [])
            ->willReturn($this->user);

        $this->parameterProvider = new ParameterProvider($this->userService);
    }

    public function testGetViewParameters(): void
    {
        $passwordExpiresAt = (new DateTimeImmutable())->add(new DateInterval('P14D'));

        $this->userService
            ->method('getPasswordInfo')
            ->with($this->user)
            ->willReturn(new PasswordInfo($passwordExpiresAt));

        $parameters = $this->parameterProvider->getViewParameters(
            $this->createFieldMock(self::EXAMPLE_USER_ID)
        );

        self::assertFalse($parameters['is_password_expired']);
        self::assertEquals($passwordExpiresAt, $parameters['password_expires_at']);
        // since PHP 8.1 computing date time includes microseconds, so the difference is not deterministic
        self::assertGreaterThanOrEqual(13, $parameters['password_expires_in']->days);
        self::assertLessThanOrEqual(14, $parameters['password_expires_in']->days);
    }

    public function testGetViewParametersWhenPasswordExpirationDateIsNull(): void
    {
        $field = $this->createFieldMock(self::EXAMPLE_USER_ID);

        $this->userService
            ->method('getPasswordInfo')
            ->with($this->user)
            ->willReturn(new PasswordInfo());

        self::assertEquals([
            'is_password_expired' => false,
            'password_expires_at' => null,
            'password_expires_in' => null,
        ], $this->parameterProvider->getViewParameters($field));
    }

    private function createFieldMock(int $userId): Field
    {
        $field = $this->createMock(Field::class);
        $field->method('__get')->with('value')->willReturn(new Value([
            'contentId' => $userId,
        ]));

        return $field;
    }
}
