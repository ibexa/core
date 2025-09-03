<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Validation\Constraint;

use Ibexa\Contracts\Core\Validation\Constraint\UniqueIdentifier;
use Ibexa\Contracts\Core\Validation\Constraint\UniqueIdentifierValidator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class UniqueIdentifierValidatorTest extends ConstraintValidatorTestCase
{
    private const EXISTING_IDENTIFIER = 'existing_identifier';

    private const EXISTING_ID = -1;

    public ?int $expectedId;

    public function testCreateStructValidate(): void
    {
        $this->expectedId = null;

        $constraint = $this->buildConstraintForValidation('id');

        $this->validator->validate(
            $this->buildObjectForValidation(null),
            $constraint,
        );

        $this->assertNoViolation();
    }

    public function testCreateStructIdentifierExists(): void
    {
        $this->expectedId = self::EXISTING_ID;

        $constraint = $this->buildConstraintForValidation('id');

        $this->validator->validate(
            $this->buildObjectForValidation(null),
            $constraint,
        );

        $this->assertUniqueConstraintViolationRaised();
    }

    public function testUpdateStructIdentifierExistsIdMatches(): void
    {
        $this->expectedId = self::EXISTING_ID;

        $constraint = $this->buildConstraintForValidation(
            'id',
        );

        $this->validator->validate(
            $this->buildObjectForValidation(
                self::EXISTING_ID,
            ),
            $constraint,
        );

        $this->assertNoViolation();
    }

    public function testUpdateStructIdentifierExistsIdMismatch(): void
    {
        $this->expectedId = self::EXISTING_ID;

        $constraint = $this->buildConstraintForValidation('id');

        $this->validator->validate(
            $this->buildObjectForValidation(-2),
            $constraint,
        );

        $this->assertUniqueConstraintViolationRaised();
    }

    public function testExistingIdNotAccessible(): void
    {
        $this->expectedId = self::EXISTING_ID;

        $object = $this->buildObjectForValidation(null);

        $constraint = $this->buildConstraintForValidation(
            'not_accessible_property.discount.id'
        );
        $this->validator->validate(
            $object,
            $constraint,
        );

        $this->assertUniqueConstraintViolationRaised();
    }

    protected function createValidator(): UniqueIdentifierValidator
    {
        return new class (PropertyAccess::createPropertyAccessor(), $this) extends UniqueIdentifierValidator {
            private UniqueIdentifierValidatorTest $test;

            public function __construct(PropertyAccessorInterface $propertyAccessor, UniqueIdentifierValidatorTest $test)
            {
                $this->test = $test;
                parent::__construct($propertyAccessor);
            }

            protected function getExistingIdForIdentifier(string $identifier): ?int
            {
                return $this->test->expectedId;
            }
        };
    }

    private function assertUniqueConstraintViolationRaised(): void
    {
        $this
            ->buildViolation('ibexa.identifier_already_exists')
            ->setParameter('%identifier%', 'existing_identifier')
            ->atPath('property.path.identifier')
            ->assertRaised();
    }

    private function buildObjectForValidation(?int $existingId): object
    {
        return (object)[
            'id' => $existingId,
            'identifier' => self::EXISTING_IDENTIFIER,
        ];
    }

    private function buildConstraintForValidation(?string $existingIdPath): UniqueIdentifier
    {
        $constraint = $this->createMock(UniqueIdentifier::class);
        $constraint->identifierPath = 'identifier';
        $constraint->existingIdPath = $existingIdPath;

        return $constraint;
    }
}
