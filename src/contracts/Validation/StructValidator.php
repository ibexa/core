<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Validation;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class StructValidator implements ValidatorInterface
{
    private ValidatorInterface $inner;

    public function __construct(ValidatorInterface $inner)
    {
        $this->inner = $inner;
    }

    public function getMetadataFor($value): MetadataInterface
    {
        return $this->inner->getMetadataFor($value);
    }

    public function hasMetadataFor($value): bool
    {
        return $this->inner->hasMetadataFor($value);
    }

    public function validate($value, $constraints = null, $groups = null): ConstraintViolationListInterface
    {
        $result = $this->inner->validate($value, $constraints, $groups);

        if (!$value instanceof ValidatorStructWrapperInterface) {
            return $result;
        }

        $unwrappedErrors = new ConstraintViolationList();

        // Skip $ from argument name
        $prefix = ltrim($value->getStructName(), '$') . '.';
        foreach ($result as $error) {
            $path = $error->getPropertyPath();
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }

            $unwrappedError = new ConstraintViolation(
                $error->getMessage(),
                $error->getMessageTemplate(),
                $error->getParameters(),
                $error->getRoot(),
                $path,
                $error->getInvalidValue(),
                $error->getPlural(),
                $error->getCode(),
                $error->getConstraint(),
                $error->getCause()
            );

            $unwrappedErrors->add($unwrappedError);
        }

        return $unwrappedErrors;
    }

    public function validateProperty(object $object, string $propertyName, $groups = null): ConstraintViolationListInterface
    {
        return $this->inner->validatePropertyValue($object, $propertyName, $groups);
    }

    public function validatePropertyValue($objectOrClass, string $propertyName, $value, $groups = null): ConstraintViolationListInterface
    {
        return $this->inner->validatePropertyValue($objectOrClass, $propertyName, $groups);
    }

    public function startContext(): ContextualValidatorInterface
    {
        return $this->inner->startContext();
    }

    public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
    {
        return $this->inner->inContext($context);
    }
}
