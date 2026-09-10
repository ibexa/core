<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Validation\Constraint;

use Ibexa\Contracts\Core\Validation\Constraint\UniqueIdentifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * @covers \Ibexa\Contracts\Core\Validation\Constraint\UniqueIdentifier
 */
final class UniqueIdentifierTest extends TestCase
{
    public function testNamedArguments(): void
    {
        $payload = new \stdClass();
        $constraint = new class(
            identifierPath: 'identifier',
            existingIdPath: 'id',
            reportErrorPath: 'identifier',
            message: 'Already exists',
            groups: ['custom'],
            payload: $payload
        ) extends UniqueIdentifier {
        };

        self::assertSame('identifier', $constraint->identifierPath);
        self::assertSame('id', $constraint->existingIdPath);
        self::assertSame('identifier', $constraint->reportErrorPath);
        self::assertSame('Already exists', $constraint->message);
        self::assertSame(['custom'], $constraint->groups);
        self::assertSame($payload, $constraint->payload);
    }

    public function testIdentifierPathIsTheOnlyRequiredArgument(): void
    {
        $constraint = new class('identifier') extends UniqueIdentifier {
        };

        self::assertSame('identifier', $constraint->identifierPath);
        self::assertNull($constraint->existingIdPath);
        self::assertNull($constraint->reportErrorPath);
        self::assertSame('ibexa.identifier_already_exists', $constraint->message);
        self::assertSame([UniqueIdentifier::DEFAULT_GROUP], $constraint->groups);
        self::assertNull($constraint->payload);
        self::assertSame([UniqueIdentifier::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testConstructorSupportsNamedArgumentsForMappingLoaders(): void
    {
        $constructor = new \ReflectionMethod(UniqueIdentifier::class, '__construct');

        self::assertNotEmpty(
            $constructor->getAttributes(HasNamedArguments::class),
            'Symfony mapping loaders (YAML/XML/attributes) rely on #[HasNamedArguments] to pass options as named arguments.'
        );
    }
}
