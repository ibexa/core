<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\EventSubscriber;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Core\Persistence\Cache\Adapter\TransactionAwareAdapterInterface;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ClearContentCacheInGracePeriodSubscriber implements EventSubscriberInterface
{
    private TransactionAwareAdapterInterface $cache;

    private CacheIdentifierGeneratorInterface $identifierGenerator;

    /** @var array<int, bool> */
    private array $contentMap = [];

    public function __construct(TransactionAwareAdapterInterface $cache, CacheIdentifierGeneratorInterface $identifierGenerator)
    {
        $this->cache = $cache;
        $this->identifierGenerator = $identifierGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'clearCache'];
    }

    public function addContentToClear(Content $content): void
    {
        $this->contentMap[$content->getId()] = false;
    }

    public function clearCache(TerminateEvent $event): void
    {
        foreach ($this->contentMap as $contentId => $v) {
            $this->cache->invalidateTags([
                $this->identifierGenerator->generateTag('content', [$contentId]),
            ]);
        }
    }
}
