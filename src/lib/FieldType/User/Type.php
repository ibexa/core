<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\User;

use DateTimeImmutable;
use DateTimeInterface;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\User\Handler as SPIUserHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\PasswordHashService;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use Ibexa\Core\Repository\User\PasswordValidatorInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use LogicException;

/**
 * The User field type.
 *
 * This field type represents a simple string.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    public const FIELD_TYPE_IDENTIFIER = 'ibexa_user';

    public const PASSWORD_TTL_SETTING = 'PasswordTTL';
    public const PASSWORD_TTL_WARNING_SETTING = 'PasswordTTLWarning';
    public const REQUIRE_UNIQUE_EMAIL = 'RequireUniqueEmail';
    public const USERNAME_PATTERN = 'UsernamePattern';

    /** @var array */
    protected array $settingsSchema = [
        self::PASSWORD_TTL_SETTING => [
            'type' => 'int',
            'default' => null,
        ],
        self::PASSWORD_TTL_WARNING_SETTING => [
            'type' => 'int',
            'default' => null,
        ],
        self::REQUIRE_UNIQUE_EMAIL => [
            'type' => 'bool',
            'default' => true,
        ],
        self::USERNAME_PATTERN => [
            'type' => 'string',
            'default' => '^[^@]+$',
        ],
    ];

    /** @var array */
    protected array $validatorConfigurationSchema = [
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

    /** @var \Ibexa\Contracts\Core\Persistence\User\Handler */
    private $userHandler;

    /** @var \Ibexa\Contracts\Core\Repository\PasswordHashService */
    private $passwordHashService;

    /** @var \Ibexa\Core\Repository\User\PasswordValidatorInterface */
    private $passwordValidator;

    public function __construct(
        SPIUserHandler $userHandler,
        PasswordHashService $passwordHashGenerator,
        PasswordValidatorInterface $passwordValidator
    ) {
        $this->userHandler = $userHandler;
        $this->passwordHashService = $passwordHashGenerator;
        $this->passwordValidator = $passwordValidator;
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return self::FIELD_TYPE_IDENTIFIER;
    }

    /**
     * @param \Ibexa\Core\FieldType\User\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return (string)$value->login;
    }

    public function isSingular(): bool
    {
        return true;
    }

    public function onlyEmptyInstance(): bool
    {
        return true;
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param array|\Ibexa\Core\FieldType\User\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\User\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_array($inputValue)) {
            $inputValue = $this->fromHash($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\User\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        // Does nothing
    }

    protected function getSortInfo(SPIValue $value): false
    {
        return false;
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        if (isset($hash['passwordUpdatedAt']) && $hash['passwordUpdatedAt'] !== null) {
            $hash['passwordUpdatedAt'] = new DateTimeImmutable('@' . $hash['passwordUpdatedAt']);
        }

        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\User\Value $value
     *
     * @return array<string, mixed>|null
     */
    public function toHash(SPIValue $value): ?array
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        $hash = (array)$value;
        if ($hash['passwordUpdatedAt'] instanceof DateTimeInterface) {
            $hash['passwordUpdatedAt'] = $hash['passwordUpdatedAt']->getTimestamp();
        }

        return $hash;
    }

    /**
     * @param \Ibexa\Core\FieldType\User\Value $value
     */
    public function toPersistenceValue(SPIValue $value): FieldValue
    {
        $value->passwordHashType = $this->getPasswordHashTypeForPersistenceValue($value);
        if (!empty($value->plainPassword)) {
            $value->passwordHash = $this->passwordHashService->createPasswordHash(
                $value->plainPassword,
                $value->passwordHashType
            );
            $value->passwordUpdatedAt = new DateTimeImmutable();
        }

        return new FieldValue(
            [
                'data' => null,
                'externalData' => $this->toHash($value),
                'sortKey' => null,
            ]
        );
    }

    private function getPasswordHashTypeForPersistenceValue(SPIValue $value): int
    {
        if (!isset($value->passwordHashType)) {
            return $this->passwordHashService->getDefaultHashType();
        }

        if (!$this->passwordHashService->isHashTypeSupported($value->passwordHashType)) {
            return $this->passwordHashService->getDefaultHashType();
        }

        return $value->passwordHashType;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function fromPersistenceValue(FieldValue $fieldValue): Value
    {
        $value = $this->acceptValue($fieldValue->externalData);
        if (!$value instanceof Value) {
            throw new InvalidArgumentException(
                '$fieldValue',
                'The given FieldValue does not contain proper User field type external data'
            );
        }

        return $value;
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Core\FieldType\User\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validate(FieldDefinition $fieldDef, SPIValue $value): array
    {
        $errors = [];

        if ($this->isEmptyValue($value)) {
            return $errors;
        }

        if (!is_string($value->login) || empty($value->login)) {
            $errors[] = new ValidationError(
                'Login required',
                null,
                [],
                'username'
            );
        }

        $pattern = sprintf('/%s/', $fieldDef->fieldSettings[self::USERNAME_PATTERN]);
        $loginFormatValid = preg_match($pattern, $value->login);
        if (!$value->hasStoredLogin && !$loginFormatValid) {
            $errors[] = new ValidationError(
                'Invalid login format',
                null,
                [],
                'username'
            );
        }

        if (!is_string($value->email) || empty($value->email)) {
            $errors[] = new ValidationError(
                'Email required',
                null,
                [],
                'email'
            );
        } elseif (false === filter_var($value->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ValidationError(
                "The given e-mail '%email%' is invalid",
                null,
                ['%email%' => $value->email],
                'email'
            );
        }

        if (!$value->hasStoredLogin && (!is_string($value->plainPassword) || empty($value->plainPassword))) {
            $errors[] = new ValidationError(
                'Password required',
                null,
                [],
                'password'
            );
        }

        if (!is_bool($value->enabled)) {
            $errors[] = new ValidationError(
                'Enabled must be boolean value',
                null,
                [],
                'enabled'
            );
        }

        if (!$value->hasStoredLogin && isset($value->login)) {
            try {
                $login = $value->login;
                $this->userHandler->loadByLogin($login);

                // If you want to change this ValidationError message, please remember to change it also in Content Forms in lib/Validator/Constraints/FieldValueValidatorMessages class
                $errors[] = new ValidationError(
                    "The user login '%login%' is used by another user. You must enter a unique login.",
                    null,
                    [
                        '%login%' => $login,
                    ],
                    'username'
                );
            } catch (NotFoundException $e) {
                // Do nothing
            }
        }

        if ($fieldDef->fieldSettings[self::REQUIRE_UNIQUE_EMAIL]) {
            try {
                $email = $value->email;
                try {
                    $user = $this->userHandler->loadByEmail($email);
                } catch (LogicException $exception) {
                    // There are multiple users with the same email
                }

                // Don't prevent email update
                if (empty($user) || $user->id != $value->contentId) {
                    // If you want to change this ValidationError message, please remember to change it also in Content Forms in lib/Validator/Constraints/FieldValueValidatorMessages class
                    $errors[] = new ValidationError(
                        "Email '%email%' is used by another user. You must enter a unique email.",
                        null,
                        [
                            '%email%' => $email,
                        ],
                        'email'
                    );
                }
            } catch (NotFoundException $e) {
                // Do nothing
            }
        }

        if (!empty($value->plainPassword)) {
            $passwordValidationErrors = $this->passwordValidator->validatePassword(
                $value->plainPassword,
                $fieldDef
            );

            $errors = array_merge($errors, $passwordValidationErrors);

            if (!empty($value->passwordHash) && $this->isNewPasswordRequired($fieldDef)) {
                $isPasswordReused = $this->passwordHashService->isValidPassword(
                    $value->plainPassword,
                    $value->passwordHash,
                    $value->passwordHashType
                );

                if ($isPasswordReused) {
                    $errors[] = new ValidationError('New password cannot be the same as old password', null, [], 'password');
                }
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function validateValidatorConfiguration(mixed $validatorConfiguration): array
    {
        $validationErrors = [];

        foreach ((array)$validatorConfiguration as $validatorIdentifier => $constraints) {
            if ($validatorIdentifier !== 'PasswordValueValidator') {
                $validationErrors[] = new ValidationError(
                    "Validator '%validator%' is unknown",
                    null,
                    [
                        'validator' => $validatorIdentifier,
                    ],
                    "[$validatorIdentifier]"
                );
            }
        }

        return $validationErrors;
    }

    public function validateFieldSettings(array $fieldSettings): array
    {
        $validationErrors = [];

        foreach ($fieldSettings as $name => $value) {
            if (!isset($this->settingsSchema[$name])) {
                $validationErrors[] = new ValidationError(
                    "Setting '%setting%' is unknown",
                    null,
                    [
                        '%setting%' => $name,
                    ],
                    "[$name]"
                );

                continue;
            }

            $error = null;
            switch ($name) {
                case self::PASSWORD_TTL_SETTING:
                    $error = $this->validatePasswordTTLSetting($name, $value);
                    break;
                case self::PASSWORD_TTL_WARNING_SETTING:
                    $error = $this->validatePasswordTTLWarningSetting($name, $value, $fieldSettings);
                    break;
            }

            if ($error !== null) {
                $validationErrors[] = $error;
            }
        }

        return $validationErrors;
    }

    private function validatePasswordTTLSetting(string $name, $value): ?ValidationError
    {
        if ($value !== null && !is_int($value)) {
            return new ValidationError(
                "Setting '%setting%' value must be of integer type",
                null,
                [
                    '%setting%' => $name,
                ],
                "[$name]"
            );
        }

        return null;
    }

    private function validatePasswordTTLWarningSetting(string $name, $value, $fieldSettings): ?ValidationError
    {
        if ($value !== null) {
            if (!is_int($value)) {
                return new ValidationError(
                    "Setting '%setting%' value must be of integer type",
                    null,
                    [
                        '%setting%' => $name,
                    ],
                    "[$name]"
                );
            }

            if ($value > 0) {
                $passwordTTL = $fieldSettings[self::PASSWORD_TTL_SETTING] ?? null;
                if ($value >= (int)$passwordTTL) {
                    return new ValidationError(
                        'Password expiration warning value should be lower then password expiration value',
                        null,
                        [],
                        "[$name]"
                    );
                }
            }
        }

        return null;
    }

    private function isNewPasswordRequired(FieldDefinition $fieldDefinition): bool
    {
        $isExplicitRequired = $fieldDefinition->validatorConfiguration['PasswordValueValidator']['requireNewPassword'] ?? false;
        if ($isExplicitRequired) {
            return true;
        }

        return $this->isPasswordTTLEnabled($fieldDefinition);
    }

    private function isPasswordTTLEnabled(FieldDefinition $fieldDefinition): bool
    {
        return ($fieldDefinition->fieldSettings[self::PASSWORD_TTL_SETTING] ?? null) > 0;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_user.name', 'ibexa_fieldtypes')->setDesc('User account'),
        ];
    }
}
