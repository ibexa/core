<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Persistence\Legacy\Metadata;

use Ibexa\Contracts\Core\Persistence\Metadata\Handler as BaseMetadataHandler;

use Ibexa\Contracts\Core\Repository\Events\Metadata\PersistMetadataEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Metadata;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Handler implements BaseMetadataHandler
{
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function persist(Metadata $metadata): void
    {
        $persistMetadataEvent = new PersistMetadataEvent($metadata);
        $this->eventDispatcher->dispatch($persistMetadataEvent);
    }
}
