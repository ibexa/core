<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Content\AsyncPublication;

use Ibexa\Contracts\Core\Persistence\ValueObject;

/**
 * Persistence value object representing a single background (asynchronous) content publication job.
 */
class AsyncPublicationJob extends ValueObject
{
    /** @var int */
    public $id;

    /** @var int */
    public $contentId;

    /** @var int */
    public $versionNo;

    public AsyncPublicationJobStatus $status;

    /** @var int */
    public $ownerId;

    /** @var int */
    public $created;

    /** @var int */
    public $modified;

    /** @var string|null */
    public $errorMessage;

    /** @var array<scalar, mixed> */
    public $data = [];
}
