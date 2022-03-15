<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Metadata;

use Ibexa\Contracts\Core\Repository\Values\Content\Metadata;
use Symfony\Contracts\EventDispatcher\Event;

final class PersistMetadataEvent extends Event
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Metadata */
    private $metadata;

    public function __construct(
        Metadata $metadata
    ) {
        $this->metadata = $metadata;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }
}

class_alias(PersistMetadataEvent::class, 'eZ\Publish\API\Repository\Events\Metadata\PersistMetadataEvent');
