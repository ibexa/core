<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\ContentType\Exception;

use Ibexa\Core\Base\Exceptions\NotFoundException;
use Throwable;

/**
 * Exception thrown when a Content Type draft is owned by a different user than the current one.
 */
final class ContentTypeOwnedBySomeoneElseException extends NotFoundException
{
    public function __construct(int $contentTypeId, ?Throwable $previous = null)
    {
        parent::__construct('The content type is owned by someone else', $contentTypeId, $previous);
    }
}
