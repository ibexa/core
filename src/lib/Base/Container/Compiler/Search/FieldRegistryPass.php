<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler\Search;

use Ibexa\Core\Search\Common\FieldRegistry;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FieldRegistryPass implements CompilerPassInterface
{
    public const string FIELD_TYPE_INDEXABLE_SERVICE_TAG = 'ibexa.field_type.indexable';
    public const string FIELD_TYPE_SERVICE_TAG = 'ibexa.field_type';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(FieldRegistry::class)) {
            return;
        }

        $fieldRegistryDefinition = $container->getDefinition(FieldRegistry::class);

        $legacyAliasMap = [];
        $serviceTags = $container->findTaggedServiceIds(self::FIELD_TYPE_SERVICE_TAG);
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (isset($attribute['legacy_alias']) && isset($attribute['alias'])) {
                    $legacyAliasMap[$attribute['alias']] = $attribute['legacy_alias'];
                }
            }
        }

        $serviceTags = $container->findTaggedServiceIds(self::FIELD_TYPE_INDEXABLE_SERVICE_TAG);
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::FIELD_TYPE_INDEXABLE_SERVICE_TAG
                        )
                    );
                }

                $fieldRegistryDefinition->addMethodCall(
                    'registerType',
                    [
                        $attribute['alias'],
                        $reference = new Reference($serviceId),
                    ]
                );

                if (isset($legacyAliasMap[$attribute['alias']])) {
                    $fieldRegistryDefinition->addMethodCall(
                        'registerType',
                        [
                            $legacyAliasMap[$attribute['alias']],
                            $reference,
                        ]
                    );
                }
            }
        }
    }
}
