<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\EventSubscriber;

use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Ibexa\Core\Repository\Collector\ContentCollector;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ClearCollectedContentCacheSubscriber implements EventSubscriberInterface
{
    private TagAwareAdapterInterface $cache;

    private CacheIdentifierGeneratorInterface $identifierGenerator;

    private ContentCollector $contentCollector;

    public function __construct(
        ContentCollector $contentCollector,
        TagAwareAdapterInterface $cache,
        CacheIdentifierGeneratorInterface $identifierGenerator
    ) {
        $this->cache = $cache;
        $this->identifierGenerator = $identifierGenerator;
        $this->contentCollector = $contentCollector;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'clearCache'];
    }

    public function clearCache(TerminateEvent $event): void
    {
        foreach ($this->contentCollector->getCollectedContentIds() as $contentId) {
            $this->cache->invalidateTags([
                $this->identifierGenerator->generateTag('content', [$contentId]),
            ]);
        }

        $this->contentCollector->reset();
    }
}
