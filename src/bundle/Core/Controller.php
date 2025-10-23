<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Templating\GlobalHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Controller extends AbstractController
{
    public function getRepository(): Repository
    {
        return $this->container->get('ibexa.api.repository');
    }

    protected function getConfigResolver(): ConfigResolverInterface
    {
        return $this->container->get('ibexa.config.resolver');
    }

    public function getGlobalHelper(): GlobalHelper
    {
        return $this->container->get('ibexa.templating.global_helper');
    }

    /**
     * Returns the root location object for current siteaccess configuration.
     *
     * @return Location
     */
    public function getRootLocation(): Location
    {
        return $this->getGlobalHelper()->getRootLocation();
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'ibexa.api.repository' => Repository::class,
                'ibexa.config.resolver' => ConfigResolverInterface::class,
                'ibexa.templating.global_helper' => GlobalHelper::class,
            ]
        );
    }
}
