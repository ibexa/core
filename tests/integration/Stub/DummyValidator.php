<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Stub;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\GenericMetadata;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DummyValidator implements ValidatorInterface
{
    public function validate(
        mixed $value,
        $constraints = null,
        $groups = null
    ): ConstraintViolationListInterface {
        return new ConstraintViolationList();
    }

    public function getMetadataFor(mixed $value): MetadataInterface
    {
        return new GenericMetadata();
    }

    public function hasMetadataFor(mixed $value): bool
    {
        return false;
    }

    public function validateProperty(
        object $object,
        string $propertyName,
        array | GroupSequence | string | null $groups = null
    ): ConstraintViolationListInterface {
        return new ConstraintViolationList();
    }

    public function validatePropertyValue(
        object | string $objectOrClass,
        string $propertyName,
        mixed $value,
        array | GroupSequence | string | null $groups = null
    ): ConstraintViolationListInterface {
        return new ConstraintViolationList();
    }

    public function startContext(): ContextualValidatorInterface
    {
        return $this->getContextualValidatorInterfaceStub();
    }

    public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
    {
        return $this->getContextualValidatorInterfaceStub();
    }

    private function getContextualValidatorInterfaceStub(): ContextualValidatorInterface
    {
        return new class() implements ContextualValidatorInterface {
            public function atPath(string $path): static
            {
                return $this;
            }

            public function validate(
                mixed $value,
                Constraint | array | null $constraints = null,
                string | GroupSequence | array | null $groups = null
            ): static {
                return $this;
            }

            public function validateProperty(
                object $object,
                string $propertyName,
                string | GroupSequence | array | null $groups = null
            ): static {
                return $this;
            }

            public function validatePropertyValue(
                object | string $objectOrClass,
                string $propertyName,
                mixed $value,
                $groups = null
            ): static {
                return $this;
            }

            public function getViolations(): ConstraintViolationListInterface
            {
                return new ConstraintViolationList();
            }
        };
    }
}
