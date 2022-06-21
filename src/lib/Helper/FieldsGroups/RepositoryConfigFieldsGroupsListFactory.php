<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Helper\FieldsGroups;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds a SettingsFieldGroupsList.
 */
final class RepositoryConfigFieldsGroupsListFactory
{
    private ConfigResolverInterface $configResolver;

    public function __construct(
        ConfigResolverInterface $configResolver
    ) {
        $this->configResolver = $configResolver;
    }

    public function build(TranslatorInterface $translator)
    {
        return new ArrayTranslatorFieldsGroupsList(
            $translator,
            $this->configResolver->getParameter('content.field_groups.default'),
            $this->configResolver->getParameter('content.field_groups.list')
        );
    }
}

class_alias(RepositoryConfigFieldsGroupsListFactory::class, 'eZ\Publish\Core\Helper\FieldsGroups\RepositoryConfigFieldsGroupsListFactory');
