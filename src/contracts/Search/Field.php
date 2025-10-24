<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Search;

use Ibexa\Contracts\Core\Persistence\ValueObject;

/**
 * Base class for document fields.
 *
 * @property-read string $name
 * @property-read mixed $value
 * @property-read FieldType $type
 */
class Field extends ValueObject
{
    /**
     * Name of the document field. Will be used to query this field.
     *
     * @var string
     */
    protected $name;

    /**
     * Value of the document field.
     *
     * Might be about anything depending on the type of the document field.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Type of the search field.
     *
     * @var FieldType
     */
    protected $type;

    /**
     * @param string $name
     * @param mixed $value
     * @param FieldType $type
     */
    public function __construct(
        $name,
        $value,
        FieldType $type
    ) {
        parent::__construct();
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getType(): FieldType
    {
        return $this->type;
    }
}
