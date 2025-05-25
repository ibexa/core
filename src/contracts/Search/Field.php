<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Search;

use Ibexa\Contracts\Core\Persistence\ValueObject;

/**
 * Base class for document fields.
 *
 * @property-read string $name
 * @property-read mixed $value
 * @property-read \Ibexa\Contracts\Core\Search\FieldType $type
 */
class Field extends ValueObject
{
    /**
     * Name of the document field. Will be used to query this field.
     */
    protected string $name;

    /**
     * Value of the document field.
     *
     * Might be about anything depending on the type of the document field.
     */
    protected mixed $value;

    /**
     * Type of the search field.
     */
    protected FieldType $type;

    public function __construct(string $name, mixed $value, FieldType $type)
    {
        parent::__construct();
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getType(): FieldType
    {
        return $this->type;
    }
}
