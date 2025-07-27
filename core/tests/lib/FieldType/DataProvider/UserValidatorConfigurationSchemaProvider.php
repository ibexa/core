<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\DataProvider;

/**
 * @internal
 */
final class UserValidatorConfigurationSchemaProvider
{
    /**
     * @return array<string, array<string, array{type: string, default: ?scalar}>>
     */
    public function getExpectedValidatorConfigurationSchema(): array
    {
        return [
            'PasswordValueValidator' => [
                'requireAtLeastOneUpperCaseCharacter' => [
                    'type' => 'int',
                    'default' => 1,
                ],
                'requireAtLeastOneLowerCaseCharacter' => [
                    'type' => 'int',
                    'default' => 1,
                ],
                'requireAtLeastOneNumericCharacter' => [
                    'type' => 'int',
                    'default' => 1,
                ],
                'requireAtLeastOneNonAlphanumericCharacter' => [
                    'type' => 'int',
                    'default' => null,
                ],
                'requireNewPassword' => [
                    'type' => 'int',
                    'default' => null,
                ],
                'requireNotCompromisedPassword' => [
                    'type' => 'bool',
                    'default' => false,
                ],
                'minLength' => [
                    'type' => 'int',
                    'default' => 10,
                ],
            ],
        ];
    }
}
