<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\MVC\View;

use Ibexa\Core\MVC\Symfony\View\View;

interface VariableProvider
{
    public function getIdentifier(): string;

    /**
     * @param array<string, mixed> $options
     */
    public function getTwigVariables(
        View $view,
        array $options = []
    ): object;
}
