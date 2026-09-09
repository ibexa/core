<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Validation\Constraint;

use Ibexa\Contracts\Core\Validation\Constraint\UniqueIdentifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

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

    public function testLegacyNamedOptions(): void
    {
        $constraint = new class(options: [
            'value' => 'identifier',
            'existingIdPath' => 'id',
            'reportErrorPath' => 'identifier',
        ]) extends UniqueIdentifier {
        };

        self::assertSame('identifier', $constraint->identifierPath);
        self::assertSame('id', $constraint->existingIdPath);
        self::assertSame('identifier', $constraint->reportErrorPath);
    }

    public function testLegacyPositionalSignature(): void
    {
        $payload = new \stdClass();
        $constraint = new class(
            ['identifierPath' => 'identifier'],
            ['custom'],
            $payload
        ) extends UniqueIdentifier {
        };

        self::assertSame('identifier', $constraint->identifierPath);
        self::assertSame(['custom'], $constraint->groups);
        self::assertSame($payload, $constraint->payload);
    }

    public function testRejectsUnknownLegacyOption(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $constraint = new class(['identifierPath' => 'identifier', 'identiferPath' => 'typo']) extends UniqueIdentifier {
        };

        self::fail(sprintf('Expected exception was not thrown while constructing %s.', $constraint::class));
    }

    public function testRequiresIdentifierPath(): void
    {
        $this->expectException(MissingOptionsException::class);

        $constraint = new class() extends UniqueIdentifier {
        };

        self::fail(sprintf('Expected exception was not thrown while constructing %s.', $constraint::class));
    }
}
