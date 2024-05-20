<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\User;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\PasswordHashService as APIPasswordHashService;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\User\PasswordInfo;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Core\FieldType\User\Type as UserType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\Validator\UserPasswordValidator;

/**
 * @internal
 */
final class PasswordValidator implements PasswordValidatorInterface
{
    private APIPasswordHashService $passwordHashService;

    public function __construct(APIPasswordHashService $passwordHashService)
    {
        $this->passwordHashService = $passwordHashService;
    }

    /**
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validatePassword(
        #[\SensitiveParameter]
        string $password,
        FieldDefinition $userFieldDefinition,
        ?User $user = null
    ): array {
        $configuration = $userFieldDefinition->getValidatorConfiguration();
        if (!isset($configuration['PasswordValueValidator'])) {
            return [];
        }

        $userPasswordValidator = new UserPasswordValidator(
            $configuration['PasswordValueValidator']
        );

        $errors = $userPasswordValidator->validate($password);

        if ($user !== null) {
            $isPasswordTTLEnabled = $this
                ->getPasswordInfo($user, $userFieldDefinition)
                ->hasExpirationDate();

            $isNewPasswordRequired = $configuration['PasswordValueValidator']['requireNewPassword'] ?? false;

            if (
                ($isPasswordTTLEnabled || $isNewPasswordRequired)
                && $this->userPasswordIsTheSame($password, $user)
            ) {
                $errors[] = new ValidationError(
                    'New password cannot be the same as old password',
                    null,
                    [],
                    'password'
                );
            }
        }

        return $errors;
    }

    public function getPasswordInfo(APIUser $user, FieldDefinition $fieldDefinition): PasswordInfo
    {
        $passwordUpdatedAt = $user->passwordUpdatedAt;
        if ($passwordUpdatedAt === null) {
            return new PasswordInfo();
        }

        $expirationDate = null;
        $expirationWarningDate = null;

        $passwordTTL = (int)$fieldDefinition->fieldSettings[UserType::PASSWORD_TTL_SETTING];
        if ($passwordTTL > 0) {
            if ($passwordUpdatedAt instanceof DateTime) {
                $passwordUpdatedAt = DateTimeImmutable::createFromMutable($passwordUpdatedAt);
            }

            $expirationDate = $passwordUpdatedAt->add(
                new DateInterval(sprintf('P%dD', $passwordTTL))
            );

            $passwordTTLWarning = (int)$fieldDefinition->fieldSettings[UserType::PASSWORD_TTL_WARNING_SETTING];
            if ($passwordTTLWarning > 0) {
                $expirationWarningDate = $expirationDate->sub(
                    new DateInterval(sprintf('P%dD', $passwordTTLWarning))
                );
            }
        }

        return new PasswordInfo($expirationDate, $expirationWarningDate);
    }

    private function userPasswordIsTheSame(string $password, APIUser $user): bool
    {
        return $this->passwordHashService->isValidPassword(
            $password,
            $user->passwordHash,
            $user->hashAlgorithm
        );
    }
}
