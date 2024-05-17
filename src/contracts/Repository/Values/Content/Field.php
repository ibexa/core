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
 */
class Field extends ValueObject
{
    /**
     * The field id.
     *
     * @todo may be not needed
     *
     * @var mixed
     */
    protected $id;

    /**
     * The field definition identifier.
     *
     * @var string
     */
    protected $fieldDefIdentifier;

    /**
     * A field type value or a value type which can be converted by the corresponding field type.
     *
     * @var mixed
     */
    protected $value;

    /**
     * the language code.
     *
     * @var string|null
     */
    protected $languageCode;

    /**
     * Field type identifier.
     *
     * @var string
     */
    protected $fieldTypeIdentifier;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFieldDefinitionIdentifier(): string
    {
        return $this->fieldDefIdentifier;
    }

    /**
     * @return mixed
     */
    public function getValue()
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
}

class_alias(Field::class, 'eZ\Publish\API\Repository\Values\Content\Field');
