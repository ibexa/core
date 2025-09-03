<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Validation\Constraint;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

abstract class UniqueIdentifierValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueIdentifier) {
            throw new UnexpectedTypeException($constraint, UniqueIdentifier::class);
        }

        if ($value === null) {
            return;
        }

        // Prevent checking not set values
        if (!$this->propertyAccessor->isReadable($value, $constraint->identifierPath)) {
            return;
        }

        $identifier = $this->propertyAccessor->getValue($value, $constraint->identifierPath);
        if ($identifier === null) {
            return;
        }

        if (!is_string($identifier)) {
            throw new UnexpectedValueException($identifier, 'string');
        }

        $currentIdForIdentifier = $this->getExistingIdForIdentifier($identifier);
        if ($currentIdForIdentifier === null) {
            return;
        }

        if (
            $constraint->existingIdPath !== null
            && $this->propertyAccessor->isReadable($value, $constraint->existingIdPath)
        ) {
            $existingId = $this->propertyAccessor->getValue($value, $constraint->existingIdPath);

            if ($existingId === $currentIdForIdentifier) {
                return;
            }
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setParameter('%identifier%', $identifier)
            ->atPath($constraint->reportErrorPath ?? $constraint->identifierPath)
            ->addViolation();
    }

    abstract protected function getExistingIdForIdentifier(string $identifier): string|int|null;
}
