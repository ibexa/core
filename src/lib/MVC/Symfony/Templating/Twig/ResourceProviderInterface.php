<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Twig;

use Twig\Template;

interface ResourceProviderInterface
{
    /**
     * @return array|Template[]
     */
    public function getFieldViewResources(): array;

    /**
     * @return array|Template[]
     */
    public function getFieldEditResources(): array;

    /**
     * @return array|Template[]
     */
    public function getFieldDefinitionViewResources(): array;

    /**
     * @return array|Template[]
     */
    public function getFieldDefinitionEditResources(): array;
}
