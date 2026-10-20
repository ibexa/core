<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Ibexa\Contracts\Core\Repository\Exceptions\ContentTypeOwnedBySomeoneElseException as APIContentTypeOwnedBySomeoneElseException;
use Throwable;

final class ContentTypeOwnedBySomeoneElseException extends NotFoundException implements APIContentTypeOwnedBySomeoneElseException
{
    public function __construct(
        int $contentTypeId,
        ?Throwable $previous = null
    ) {
        parent::__construct('The content type is owned by someone else', $contentTypeId, $previous);
    }
}
