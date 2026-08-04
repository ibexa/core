<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\AsyncPublication;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Represents a single background (asynchronous) content publication job.
 *
 * @property-read int $id The ID of the job
 * @property-read int $contentId The ID of the content being published
 * @property-read int $versionNo The version being published
 * @property-read AsyncPublicationJobStatus $status One of the STATUS_* constants
 * @property-read int $ownerId The ID of the user who triggered the publication
 * @property-read \DateTimeInterface $created When the job was queued
 * @property-read \DateTimeInterface $modified Last status change
 * @property-read string|null $errorMessage Failure details, when the job has failed
 * @property-read array<scalar, mixed> $data Optional context data (e.g. translations)
 */
class AsyncPublicationJob extends ValueObject
{
    /** @var int */
    protected $id;

    /** @var int */
    protected $contentId;

    /** @var int */
    protected $versionNo;

    protected AsyncPublicationJobStatus $status;

    /** @var int */
    protected $ownerId;

    /** @var \DateTimeInterface */
    protected $created;

    /** @var \DateTimeInterface */
    protected $modified;

    /** @var string|null */
    protected $errorMessage;

    /** @var array<scalar, mixed> */
    protected $data = [];
}
