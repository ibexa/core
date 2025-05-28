<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler;

use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class AbstractFieldTypeBasedPass implements CompilerPassInterface
{
    public const string FIELD_TYPE_SERVICE_TAG = 'ibexa.field_type';

    public const array FIELD_TYPE_SERVICE_TAGS = [
        self::FIELD_TYPE_SERVICE_TAG,
    ];

    public function getFieldTypeServiceIds(ContainerBuilder $container): iterable
    {
        $serviceTags = $container->findTaggedServiceIds(self::FIELD_TYPE_SERVICE_TAG);
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::FIELD_TYPE_SERVICE_TAG
                        )
                    );
                }
            }
        }

        return $serviceTags;
    }

    abstract public function process(ContainerBuilder $container): void;
}
