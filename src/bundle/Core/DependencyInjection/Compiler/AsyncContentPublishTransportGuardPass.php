<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Guards the asynchronous content publishing requirement at container build time: when the
 * "async_content_publish" repository flag is enabled, the Ibexa Messenger transport must be
 * configured. Rather than silently degrading to the synchronous flow, this surfaces a clear
 * configuration error during application bootstrapping.
 */
final class AsyncContentPublishTransportGuardPass implements CompilerPassInterface
{
    private const string TRANSPORT_ID = 'ibexa.messenger.transport';
    private const string TRANSPORT_DSN_PARAM = 'ibexa.messenger.transport_dsn';

    public function process(ContainerBuilder $container): void
    {
        if (!$this->isAsyncContentPublishEnabled($container)) {
            return;
        }

        // The transport must be configured, i.e. the ibexa/messenger bundle must provide it.
        if (
            !$container->hasDefinition(self::TRANSPORT_ID)
            || !$container->hasParameter(self::TRANSPORT_DSN_PARAM)
        ) {
            throw new InvalidConfigurationException(
                'Asynchronous content publishing ("async_content_publish") is enabled but the Ibexa '
                . 'Messenger transport "' . self::TRANSPORT_ID . '" is not configured. Install and '
                . 'configure the ibexa/messenger bundle, or disable the "async_content_publish" flag.'
            );
        }

        // Reject synchronous/in-memory no-op transports, which would process publishing inside the
        // editor request and silently defeat asynchronous behaviour.
        /** @var string $dsn */
        $dsn = $container->getParameter(self::TRANSPORT_DSN_PARAM);
        if ($this->isSynchronousTransport($dsn)) {
            throw new InvalidConfigurationException(
                sprintf(
                    'Asynchronous content publishing ("async_content_publish") is enabled but the Ibexa '
                    . 'Messenger transport DSN "%s" is a synchronous/in-memory transport. Configure async transport instead.',
                    $dsn
                )
            );
        }
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

    private function isSynchronousTransport(string $dsn): bool
    {
        return str_starts_with($dsn, 'sync://') || str_starts_with($dsn, 'in-memory://');
    }
}
