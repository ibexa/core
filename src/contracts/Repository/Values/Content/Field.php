<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a field of a content object.
 *
 * @property-read mixed $id an internal id of the field
 * @property-read string $fieldDefIdentifier the field definition identifier
 * @property-read mixed $value the value of the field
 * @property-read string $languageCode the language code of the field
 * @property-read string $fieldTypeIdentifier field type identifier
 */
class Field extends ValueObject
{
    /**
     * The field id.
     *
     * Value of `null` indicates the field is virtual
     * and is not persisted (yet).
     */
    protected ?int $id = null;

    /**
     * The field definition identifier.
     */
    protected string $fieldDefIdentifier;

    /**
     * A field type value or a value type which can be converted by the corresponding field type.
     */
    protected mixed $value;

    /**
     * The language code.
     */
    protected ?string $languageCode;

    /**
     * Field type identifier.
     */
    protected string $fieldTypeIdentifier;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFieldDefinitionIdentifier(): string
    {
        return $this->fieldDefIdentifier;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public function getFieldTypeIdentifier(): string
    {
        return $this->fieldTypeIdentifier;
    }

    /**
     * @phpstan-assert-if-true !null $this->getId()
     */
    public function isVirtual(): bool
    {
        return null === $this->id;
    }
}
