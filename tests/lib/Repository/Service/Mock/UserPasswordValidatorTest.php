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

/**
 * @covers \Ibexa\Core\Repository\Validator\UserPasswordValidator
 */
class UserPasswordValidatorTest extends TestCase
{
    /**
     * @dataProvider dateProviderForValidate
     */
    public function testValidate(
        array $constraints,
        string $password,
        array $expectedErrors
    ) {
        $validator = new UserPasswordValidator($constraints);

        self::assertEqualsCanonicalizing($expectedErrors, $validator->validate($password), '');
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
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => true,
                ],
                // 64 chars, very unlikely to ever be in a breach
                bin2hex(random_bytes(32)),
                [/* No errors */],
            ],
            [
                [
                    'minLength' => -1,
                    'requireAtLeastOneLowerCaseCharacter' => false,
                    'requireAtLeastOneUpperCaseCharacter' => false,
                    'requireAtLeastOneNumericCharacter' => false,
                    'requireAtLeastOneNonAlphanumericCharacter' => false,
                    'requireNotCompromisedPassword' => true,
                ],
                'secret',
                [
                    new ValidationError(
                        'This password has been leaked in a data breach, it must not be used. '
                        . 'Please use another password.',
                        null,
                        [],
                        'password'
                    ),
                ],
            ],
        ];
    }
}
