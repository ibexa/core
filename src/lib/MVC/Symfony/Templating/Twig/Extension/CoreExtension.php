<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Core\MVC\Symfony\Templating\GlobalHelper;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class CoreExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var GlobalHelper */
    private $globalHelper;

    public function __construct(GlobalHelper $globalHelper)
    {
        $this->globalHelper = $globalHelper;
    }

    /**
     * @return array
     */
    public function getGlobals(): array
    {
        return [
            'ibexa' => $this->globalHelper,
        ];
    }
}
