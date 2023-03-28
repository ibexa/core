<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\Validator\UserPasswordValidator;
use Ibexa\Tests\Core\Search\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \Ibexa\Core\Repository\Validator\UserPasswordValidator
 */
class UserPasswordValidatorTest extends TestCase
{
    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private ValidatorInterface $symfonyValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->symfonyValidator = $this->createMock(ValidatorInterface::class);
    }

    /**
     * @dataProvider dateProviderForValidate
     */
    public function testValidate(array $constraints, string $password, array $expectedErrors)
    {
        $validator = new UserPasswordValidator($constraints, $this->symfonyValidator);

        $this->assertEqualsCanonicalizing($expectedErrors, $validator->validate($password), '');
    }

    public function dateProviderForValidate(): array
    {
        return [
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                'pass',
                [/* No errors */],
            ],
            [
                [
                    'minLength' => 6,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                '123',
                [
                    new ValidationError('User password must be at least %length% characters long', null, [
                        '%length%' => 6,
                    ], 'password'),
                ],
            ],
            [
                [
                    'minLength' => 6,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                '123456!',
                [/* No errors */],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => true,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                'PASS',
                [
                    new ValidationError('User password must include at least one lower case letter', null, [], 'password'),
                ],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => true,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                'PaSS',
                [/* No errors */],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => true,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                'pass',
                [
                    new ValidationError('User password must include at least one upper case letter', null, [], 'password'),
                ],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => true,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                'pAss',
                [/* No errors */],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => true,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                'pass',
                [
                    new ValidationError('User password must include at least one number', null, [], 'password'),
                ],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => true,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => false,
                ],
                'pass1',
                [/* No errors */],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => true,
                    'requireNotCompromisedPassword' => false,
                ],
                'pass',
                [
                    new ValidationError('User password must include at least one special character', null, [], 'password'),
                ],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => true,
                    'requireNotCompromisedPassword' => false,
                ],
                'pass!',
                [/* No errors */],
            ],
            [
                [
                    'minLength' => 6,
                    'requireAtLeastOneLowerCaseCharacter' => true,
                    'requireAtLeastOneUpperCaseCharacter' => true,
                    'requireAtLeastOneNumericCharacter' => true,
                    'requireAtLeastOneNonAlphanumericCharacter' => true,
                    'requireNotCompromisedPassword' => false,
                ],
                'asdf',
                [
                    new ValidationError('User password must be at least %length% characters long', null, [
                        '%length%' => 6,
                    ], 'password'),
                    new ValidationError('User password must include at least one upper case letter', null, [], 'password'),
                    new ValidationError('User password must include at least one number', null, [], 'password'),
                    new ValidationError('User password must include at least one special character', null, [], 'password'),
                ],
            ],
            [
                [
                    'minLength' => 6,
                    'requireAtLeastOneLowerCaseCharacter' => true,
                    'requireAtLeastOneUpperCaseCharacter' => true,
                    'requireAtLeastOneNumericCharacter' => true,
                    'requireAtLeastOneNonAlphanumericCharacter' => true,
                    'requireNotCompromisedPassword' => false,
                ],
                'H@xxi0r!',
                [/* No errors */],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForValidateNotCompromised
     */
    public function testValidateNotCompromised(array $constraints, string $password, array $expectedErrors): void
    {
        $this->symfonyValidator
            ->method('validate')
            ->with($password, null)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $validator = new UserPasswordValidator($constraints, $this->symfonyValidator);

        $this->assertEqualsCanonicalizing($expectedErrors, $validator->validate($password), '');
    }

    public function dataProviderForValidateNotCompromised(): array
    {
        return [
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => true,
                ],
                // The actual value doesn't matter here as we're mocking the Symfony validator.
                'b9634b07bef1d1f99495b97ba4b9a1ba19f353eb9696443996b82fad93f37b67',
                [/* No errors */],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForValidateCompromised
     */
    public function testValidateCompromised(array $constraints, string $password, array $expectedErrors): void
    {
        $constraintViolationList = new ConstraintViolationList([
            new ConstraintViolation($expectedErrors[0]->getTranslatableMessage(), null, [], $password, null, $password),
        ]);

        $this->symfonyValidator
            ->method('validate')
            ->with($password, null)
            ->willReturn($constraintViolationList);

        $validator = new UserPasswordValidator($constraints, $this->symfonyValidator);

        $this->assertEqualsCanonicalizing($expectedErrors, $validator->validate($password), '');
    }

    public function dataProviderForValidateCompromised(): array
    {
        $errorMessage = <<<EOT
This password has been leaked in a data breach, it must not be used. Please use another password.
EOT;

        return [
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => true,
                ],
                // The actual value doesn't matter here as we're mocking the Symfony validator.
                'publish',
                [
                    new ValidationError($errorMessage, null, [], 'password'),
                ],
            ],
        ];
    }
}

class_alias(UserPasswordValidatorTest::class, 'eZ\Publish\Core\Repository\Tests\Service\Mock\UserPasswordValidatorTest');
