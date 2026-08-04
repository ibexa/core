<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Contracts\Core\Repository\ContentPublisherInterface;
use Ibexa\Core\Repository\ContentService\AsynchronousContentPublisher;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Selects the content publishing strategy at container build time. The default alias for
 * {@see ContentPublisherInterface} points to the synchronous strategy; when the
 * "async_content_publish" repository flag is enabled, this overrides it with the asynchronous
 * strategy so that only a single strategy is ever wired.
 */
final class AsyncContentPublisherStrategyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$this->isAsyncContentPublishEnabled($container)) {
            return;
        }

        $container->setAlias(
            ContentPublisherInterface::class,
            AsynchronousContentPublisher::class,
        );
    }

    private function isAsyncContentPublishEnabled(ContainerBuilder $container): bool
    {
        if (!$container->hasParameter('ibexa.repositories')) {
            return false;
        }

        /** @var array<string, array<string, mixed>> $repositories */
        $repositories = (array) $container->getParameter('ibexa.repositories');

        return array_any(
            $repositories,
            static fn (array $repositoryConfig) => true === $repositoryConfig['async_content_publish'],
        );
    }
}
