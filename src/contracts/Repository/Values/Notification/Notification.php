<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Notification;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a notification value.
 *
 * @phpstan-property-read int $id The ID of notification
 * @phpstan-property-read int $ownerId The ID of notification owner
 * @phpstan-property-read bool $isPending True if notification is unreaded
 * @phpstan-property-read string $type Notification type
 * @phpstan-property-read \DateTimeInterface $created Creation date.
 * @phpstan-property-read array $data Optional context data
 */
class Notification extends ValueObject
{
    /** @var int */
    protected $id;

    /** @var int */
    protected $ownerId;

    /** @var bool */
    protected $isPending;

    /** @var string */
    protected $type;

    /** @var \DateTimeInterface */
    protected $created;

    /** @var array */
    protected $data = [];
}

class_alias(Notification::class, 'eZ\Publish\API\Repository\Values\Notification\Notification');
