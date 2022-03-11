<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a metadata in the repository.
 *
 * @property-read mixed $identifier the metadata unique identifier.
 * @property-read mixed $value the metadata value.
 */
abstract class Metadata extends ValueObject
{
    /**
     * The metadata unique identifier.
     */
    protected string $identifier;

    /**
     * the metadata value.
     *
     * @var mixed
     */
    protected $value;
}

class_alias(Metadata::class, 'eZ\Publish\API\Repository\Values\Content\Metadata');
