<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\Exception;

use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * Exception thrown if no converter for a type was found.
 */
class NotFound extends NotFoundException
{
    /**
     * Creates a new exception for $typeName.
     */
    public function __construct(mixed $typeName)
    {
        parent::__construct(
            'Persistence Field Value Converter',
            $typeName
        );
    }
}
