<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

/**
 * @internal
 */
final class MissingUserFieldTypeException extends ContentValidationException
{
    public function __construct(ContentType $contentType, string $fieldType)
    {
        parent::__construct(
            'The provided content type "%contentType%" does not contain the %fieldType% Field Type',
            [
                'contentType' => $contentType->identifier,
                'fieldType' => $fieldType,
            ]
        );
    }
}
