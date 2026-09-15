<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Validator\FileSizeValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileSizeValidator::class)]
#[CoversMethod(FileSizeValidator::class, 'validate')]
#[CoversMethod(Validator::class, 'getMessage')]
#[Group('fieldType')]
#[Group('validator')]
class FileSizeValidatorTest extends TestCase
{
    protected static function getMaxFileSize(): int
    {
        return 4096;
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(
            Validator::class,
            new FileSizeValidator()
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException
     */
    public function testConstraintsInitializeGet(): void
    {
        $constraints = [
            'maxFileSize' => 4096,
        ];
        $validator = new FileSizeValidator();
        $validator->initializeWithConstraints(
            $constraints
        );
        self::assertSame($constraints['maxFileSize'], $validator->maxFileSize);
    }

    public function testGetConstraintsSchema(): void
    {
        $constraintsSchema = [
            'maxFileSize' => [
                'type' => 'int',
                'default' => false,
            ],
        ];
        $validator = new FileSizeValidator();
        self::assertSame($constraintsSchema, $validator->getConstraintsSchema());
    }

    public function testConstraintsSetGet(): void
    {
        $constraints = [
            'maxFileSize' => 4096,
        ];
        $validator = new FileSizeValidator();
        $validator->maxFileSize = $constraints['maxFileSize'];
        self::assertSame($constraints['maxFileSize'], $validator->maxFileSize);
    }

    public function testInitializeBadConstraint(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $constraints = [
            'unexisting' => 0,
        ];
        $validator = new FileSizeValidator();
        $validator->initializeWithConstraints(
            $constraints
        );
    }

    public function testSetBadConstraint(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $validator = new FileSizeValidator();
        $validator->unexisting = 0;
    }

    public function testGetBadConstraint(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $validator = new FileSizeValidator();
        $null = $validator->unexisting;
    }

    /**
     * Tests validating a correct value.
     *
     * @param int $size
     */
    #[DataProvider('providerForValidateOK')]
    public function testValidateCorrectValues($size)
    {
        self::markTestSkipped('BinaryFile field type does not use this validator anymore.');
        $validator = new FileSizeValidator();
        $validator->maxFileSize = 4096;
        self::assertTrue($validator->validate($this->getBinaryFileValue($size)));
        self::assertSame([], $validator->getMessage());
    }

    /**
     * @param int $size
     *
     * @return \Ibexa\Core\FieldType\BinaryFile\Value
     */
    protected function getBinaryFileValue($size)
    {
        self::markTestSkipped('BinaryFile field type does not use this validator anymore.');
        $value = new BinaryFileValue($this->createMock(IOServiceInterface::class));
        $value->file = new BinaryFile(['size' => $size]);

        return $value;
    }

    public static function providerForValidateOK()
    {
        return [
            [0],
            [512],
            [4096],
        ];
    }

    /**
     * Tests validating a wrong value.
     */
    #[DataProvider('providerForValidateKO')]
    public function testValidateWrongValues($size, $message, $values)
    {
        self::markTestSkipped('BinaryFile field type does not use this validator anymore.');
        $validator = new FileSizeValidator();
        $validator->maxFileSize = $this->getMaxFileSize();
        self::assertFalse($validator->validate($this->getBinaryFileValue($size)));
        $messages = $validator->getMessage();
        self::assertCount(1, $messages);
        self::assertInstanceOf(
            ValidationError::class,
            $messages[0]
        );
        self::assertInstanceOf(
            Plural::class,
            $messages[0]->getTranslatableMessage()
        );
        self::assertEquals(
            $message[0],
            $messages[0]->getTranslatableMessage()->singular
        );
        self::assertEquals(
            $message[1],
            $messages[0]->getTranslatableMessage()->plural
        );
        self::assertEquals(
            $values,
            $messages[0]->getTranslatableMessage()->values
        );
    }

    public static function providerForValidateKO()
    {
        return [
            [
                8192,
                [
                    'The file size cannot exceed %size% byte.',
                    'The file size cannot exceed %size% bytes.',
                ],
                ['%size%' => self::getMaxFileSize()],
            ],
        ];
    }

    /**
     * Tests validation of constraints.
     *
     *
     * @param array<string, mixed> $constraints
     */
    #[DataProvider('providerForValidateConstraintsOK')]
    public function testValidateConstraintsCorrectValues(array $constraints): void
    {
        $validator = new FileSizeValidator();

        self::assertEmpty(
            $validator->validateConstraints($constraints)
        );
    }

    /**
     * @return array<array{array{maxFileSize: int|false}|array{}}>
     */
    public static function providerForValidateConstraintsOK(): array
    {
        return [
            [
                [],
                ['maxFileSize' => false],
                ['maxFileSize' => 0],
                ['maxFileSize' => -5],
                ['maxFileSize' => 4096],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $constraints
     * @param string[] $expectedMessages
     * @param array<string, scalar> $values
     */
    #[DataProvider('providerForValidateConstraintsKO')]
    public function testValidateConstraintsWrongValues(array $constraints, array $expectedMessages, array $values): void
    {
        $validator = new FileSizeValidator();
        $messages = $validator->validateConstraints($constraints);

        self::assertInstanceOf(
            Message::class,
            $messages[0]->getTranslatableMessage()
        );
        self::assertEquals(
            $expectedMessages[0],
            $messages[0]->getTranslatableMessage()->message
        );
        self::assertEquals(
            $values,
            $messages[0]->getTranslatableMessage()->values
        );
    }

    /**
     * @return array<array{array<string, mixed>, string[], array<string, scalar>}>
     */
    public static function providerForValidateConstraintsKO(): array
    {
        return [
            [
                ['maxFileSize' => true],
                ["Validator parameter '%parameter%' value must be of integer type"],
                ['%parameter%' => 'maxFileSize'],
            ],
            [
                ['maxFileSize' => 'five thousand bytes'],
                ["Validator parameter '%parameter%' value must be of integer type"],
                ['%parameter%' => 'maxFileSize'],
            ],
            [
                ['maxFileSize' => new \DateTime()],
                ["Validator parameter '%parameter%' value must be of integer type"],
                ['%parameter%' => 'maxFileSize'],
            ],
            [
                ['brljix' => 12345],
                ["Validator parameter '%parameter%' is unknown"],
                ['%parameter%' => 'brljix'],
            ],
        ];
    }
}
