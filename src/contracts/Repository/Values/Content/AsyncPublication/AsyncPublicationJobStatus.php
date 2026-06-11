<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Repository\Values\Content\AsyncPublication;

enum AsyncPublicationJobStatus: string
{
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case FAILED = 'failed';
}
