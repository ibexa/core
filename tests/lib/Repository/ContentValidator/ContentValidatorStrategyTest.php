<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\ContentValidator;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Validator\ContentValidator;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\Strategy\ContentValidator\ContentValidatorStrategy;
use Ibexa\Core\Repository\Values\ObjectState\ObjectState;
use PHPUnit\Framework\TestCase;

final class ContentValidatorStrategyTest extends TestCase
{
    public function testUnknownValidationObject(): void
    {
        $contentValidatorStrategy = new ContentValidatorStrategy([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument \'$object\' is invalid: Validator for Ibexa\Core\Repository\Values\ObjectState\ObjectState type not found.'
        );
        $contentValidatorStrategy->validate(new ObjectState());
    }

    public function testKnownValidationObject(): void
    {
        $contentValidatorStrategy = new ContentValidatorStrategy([
            $this->buildContentValidator(ObjectState::class, [1 => ['eng-GB' => 'test']]),
        ]);

        $errors = $contentValidatorStrategy->validate(new ObjectState());
        self::assertEquals([1 => ['eng-GB' => new ValidationError('test')]], $errors);
    }

    public function testSupportsUnknownValidationObject(): void
    {
        $contentValidatorStrategy = new ContentValidatorStrategy([]);
        $supports = $contentValidatorStrategy->supports(new ObjectState());

        self::assertFalse($supports);
    }

    public function testSupportsKnownValidationObject(): void
    {
        $contentValidatorStrategy = new ContentValidatorStrategy([
            $this->buildContentValidator(ObjectState::class, [1 => ['eng-GB' => 'test']]),
        ]);

        $supports = $contentValidatorStrategy->supports(new ObjectState());

        self::assertTrue($supports);
    }

    public function testMergeValidationErrors(): void
    {
        $contentValidatorStrategy = new ContentValidatorStrategy([
            $this->buildContentValidator(ObjectState::class, [
                123 => ['eng-GB' => '123-eng-GB'],
                456 => ['pol-PL' => '456-pol-PL'],
            ]),
            $this->buildContentValidator(ObjectState::class, []),
            $this->buildContentValidator(ObjectState::class, [
                321 => ['pol-PL' => '321-pol-PL'],
            ]),
            $this->buildContentValidator(ObjectState::class, [
                2345 => ['eng-GB' => '2345-eng-GB'],
                456 => ['eng-GB' => '456-eng-GB'],
            ]),
        ]);

        $errors = $contentValidatorStrategy->validate(new ObjectState());
        self::assertEquals([
            123 => ['eng-GB' => new ValidationError('123-eng-GB')],
            321 => ['pol-PL' => new ValidationError('321-pol-PL')],
            456 => [
                'pol-PL' => new ValidationError('456-pol-PL'),
                'eng-GB' => new ValidationError('456-eng-GB'),
            ],
            2345 => ['eng-GB' => new ValidationError('2345-eng-GB')],
        ], $errors);
    }

    /**
     * @phpstan-param array<int, array<string, string>> $validationReturn
     */
    private function buildContentValidator(
        string $classSupport,
        array $validationReturn
    ): ContentValidator {
        return new readonly class($classSupport, $validationReturn) implements ContentValidator {
            /**
             * @param array<int, array<string, string>> $validationReturn
             */
            public function __construct(
                private string $classSupport,
                private array $validationReturn
            ) {}

            public function supports(ValueObject $object): bool
            {
                return $object instanceof $this->classSupport;
            }

            public function validate(
                ValueObject $object,
                array $context = [],
                ?array $fieldIdentifiers = null
            ): array {
                // map validation message string to an expected instance of ValidationError
                return array_map(
                    static fn (array $errors): array => array_map(
                        static fn (string $error): ValidationError => new ValidationError($error),
                        $errors
                    ),
                    $this->validationReturn
                );
            }
        };
    }
}
