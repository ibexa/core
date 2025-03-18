<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType as SPIFieldType;
use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition as APIFieldDefinition;
use Ibexa\Core\FieldType\Value;
use Ibexa\Core\Repository\Values\ContentType\FieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class APIFieldTypeTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private MockObject $innerFieldType;

    /** @var \Ibexa\Core\Repository\Values\ContentType\FieldType */
    private FieldType $fieldType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerFieldType = $this->createMock(SPIFieldType::class);
        $this->fieldType = new FieldType($this->innerFieldType);
    }

    public function testValidateValidatorConfigurationNoError(): void
    {
        $validatorConfig = ['foo' => 'bar'];
        $validationErrors = [];
        $this->innerFieldType
            ->expects(self::once())
            ->method('validateValidatorConfiguration')
            ->with($validatorConfig)
            ->willReturn($validationErrors);

        self::assertSame($validationErrors, $this->fieldType->validateValidatorConfiguration($validatorConfig));
    }

    public function testValidateValidatorConfiguration(): void
    {
        $validatorConfig = ['foo' => 'bar'];
        $validationErrors = [
            $this->createMock(ValidationError::class),
            $this->createMock(ValidationError::class),
            $this->createMock(ValidationError::class),
        ];
        $this->innerFieldType
            ->expects(self::once())
            ->method('validateValidatorConfiguration')
            ->with($validatorConfig)
            ->willReturn($validationErrors);

        self::assertSame($validationErrors, $this->fieldType->validateValidatorConfiguration($validatorConfig));
    }

    public function testValidateFieldSettingsNoError(): void
    {
        $fieldSettings = ['foo' => 'bar'];
        $validationErrors = [];
        $this->innerFieldType
            ->expects(self::once())
            ->method('validateFieldSettings')
            ->with($fieldSettings)
            ->willReturn($validationErrors);

        self::assertSame($validationErrors, $this->fieldType->validateFieldSettings($fieldSettings));
    }

    public function testValidateFieldSettings(): void
    {
        $fieldSettings = ['foo' => 'bar'];
        $validationErrors = [
            $this->createMock(ValidationError::class),
            $this->createMock(ValidationError::class),
            $this->createMock(ValidationError::class),
        ];
        $this->innerFieldType
            ->expects(self::once())
            ->method('validateFieldSettings')
            ->with($fieldSettings)
            ->willReturn($validationErrors);

        self::assertSame($validationErrors, $this->fieldType->validateFieldSettings($fieldSettings));
    }

    public function testValidateValueNoError(): void
    {
        $fieldDefinition = $this->getMockForAbstractClass(APIFieldDefinition::class);
        $value = $this->getMockForAbstractClass(Value::class);
        $validationErrors = [];
        $this->innerFieldType
            ->expects(self::once())
            ->method('validate')
            ->with($fieldDefinition, $value)
            ->willReturn($validationErrors);

        self::assertSame($validationErrors, $this->fieldType->validateValue($fieldDefinition, $value));
    }

    public function testValidateValue(): void
    {
        $fieldDefinition = $this->getMockForAbstractClass(APIFieldDefinition::class);
        $value = $this->getMockForAbstractClass(Value::class);
        $validationErrors = [
            $this->createMock(ValidationError::class),
            $this->createMock(ValidationError::class),
            $this->createMock(ValidationError::class),
        ];
        $this->innerFieldType
            ->expects(self::once())
            ->method('validate')
            ->with($fieldDefinition, $value)
            ->willReturn($validationErrors);

        self::assertSame($validationErrors, $this->fieldType->validateValue($fieldDefinition, $value));
    }
}
