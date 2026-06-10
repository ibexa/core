<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Content\AsyncPublication;

use Ibexa\Contracts\Core\Persistence\ValueObject;

class UpdateStruct extends ValueObject
{
    public ?AsyncPublicationJobStatus $status = null;

    /** @var string|null */
    public $errorMessage;

    /** @var int|null */
    public $modified;
}
