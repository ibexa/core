<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\User;

use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType;
use Ibexa\Core\Repository\User\PasswordHashService;
use PHPUnit\Framework\TestCase;

final class PasswordHashServiceTest extends TestCase
{
    private const int NON_EXISTING_PASSWORD_HASH = PHP_INT_MAX;

    private PasswordHashService $passwordHashService;

    protected function setUp(): void
    {
        $this->passwordHashService = new PasswordHashService();
    }

    public function testGetSupportedHashTypes(): void
    {
        self::assertEquals(
            [
                User::PASSWORD_HASH_BCRYPT,
                User::PASSWORD_HASH_PHP_DEFAULT,
                User::PASSWORD_HASH_INVALID,
            ],
            $this->passwordHashService->getSupportedHashTypes()
        );
    }

    public function testIsHashTypeSupported(): void
    {
        self::assertTrue($this->passwordHashService->isHashTypeSupported(User::DEFAULT_PASSWORD_HASH));
        self::assertFalse($this->passwordHashService->isHashTypeSupported(self::NON_EXISTING_PASSWORD_HASH));
    }

    public function testCreatePasswordHashExceptionHidesSensitiveParameter(): void
    {
        $ignoreArgs = ini_get('zend.exception_ignore_args');
        $paramMax = ini_get('zend.exception_string_param_max_len');

        ini_set('zend.exception_ignore_args', '0');
        ini_set('zend.exception_string_param_max_len', '10');

        $password = 'secret';

        try {
            $this->passwordHashService->createPasswordHash($password, self::NON_EXISTING_PASSWORD_HASH);
            self::fail(sprintf(
                'Expected exception %s to be thrown.',
                UnsupportedPasswordHashType::class,
            ));
        } catch (UnsupportedPasswordHashType $e) {
            $stackTrace = $e->getTrace();
            self::assertIsArray($stackTrace);
            self::assertGreaterThan(1, count($stackTrace));
            self::assertArrayHasKey('function', $stackTrace[0]);
            self::assertEquals('createPasswordHash', $stackTrace[0]['function']);
            self::assertArrayHasKey('args', $stackTrace[0]);

            self::assertInstanceOf(\SensitiveParameterValue::class, $stackTrace[0]['args'][0]);
        }

        ini_set('zend.exception_ignore_args', (string)$ignoreArgs);
        ini_set('zend.exception_string_param_max_len', (string)$paramMax);
    }
}
