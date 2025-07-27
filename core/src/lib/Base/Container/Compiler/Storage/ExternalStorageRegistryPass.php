<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler\Storage;

use Ibexa\Core\Persistence\Legacy\Content\StorageRegistry;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExternalStorageRegistryPass implements CompilerPassInterface
{
    public const string EXTERNAL_STORAGE_HANDLER_SERVICE_TAG = 'ibexa.field_type.storage.external.handler';
    public const string EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG = 'ibexa.field_type.storage.external.handler.gateway';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(StorageRegistry::class)) {
            return;
        }

        $externalStorageRegistryDefinition = $container->getDefinition(
            StorageRegistry::class
        );

        // Gateways for external storage handlers.
        // Alias attribute is the corresponding field type string.
        $externalStorageGateways = [];

        $serviceTags = $container->findTaggedServiceIds(
            self::EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG
        );
        // Referencing the services by alias (field type string)
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG
                        )
                    );
                }

                if (!isset($attribute['identifier'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG
                        )
                    );
                }

                $externalStorageGateways[$attribute['alias']] = [
                    'id' => $serviceId,
                    'identifier' => $attribute['identifier'],
                ];
            }
        }

        $serviceTags = $container->findTaggedServiceIds(self::EXTERNAL_STORAGE_HANDLER_SERVICE_TAG);
        // External storage handlers for field types that need them.
        // Alias attribute is the field type string.
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::EXTERNAL_STORAGE_HANDLER_SERVICE_TAG
                        )
                    );
                }

                $externalStorageRegistryDefinition->addMethodCall(
                    'register',
                    [
                        $attribute['alias'],
                        new Reference($serviceId),
                    ]
                );
            }
        }
    }
}
