<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Helper\FieldsGroups;

use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds a SettingsFieldGroupsList.
 */
final readonly class RepositoryConfigFieldsGroupsListFactory
{
    private RepositoryConfigurationProviderInterface $configProvider;

    public function __construct(RepositoryConfigurationProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function build(TranslatorInterface $translator): FieldsGroupsList
    {
        $repositoryConfig = $this->configProvider->getRepositoryConfig();

        return new ArrayTranslatorFieldsGroupsList(
            $translator,
            $repositoryConfig['fields_groups']['default'],
            $repositoryConfig['fields_groups']['list']
        );
    }
}
