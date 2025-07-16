<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

final readonly class PlaceholderAliasGeneratorConfigurator
{
    /**
     * @phpstan-param array<string, array{
     *     provider: string,
     *     options: array<string, mixed>,
     *     verify_binary_data_availability?: bool
     * }> $providersConfig
     */
    public function __construct(
        private ConfigResolverInterface $configResolver,
        private PlaceholderProviderRegistry $providerRegistry,
        private array $providersConfig
    ) {
    }

    public function configure(PlaceholderAliasGenerator $generator): void
    {
        $binaryHandlerName = $this->configResolver->getParameter('io.binarydata_handler');

        if (isset($this->providersConfig[$binaryHandlerName])) {
            $config = $this->providersConfig[$binaryHandlerName];

            $provider = $this->providerRegistry->getProvider($config['provider']);

            $generator->setPlaceholderProvider($provider, $config['options']);
            $generator->setVerifyBinaryDataAvailability($config['verify_binary_data_availability'] ?? false);
        }
    }
}
