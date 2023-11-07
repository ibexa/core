<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Matcher\ContentBased;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\MVC\Symfony\Controller\Content\PreviewController;
use Ibexa\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Ibexa\Core\MVC\Symfony\View\View;

/**
 * @internal
 */
final class IsPreview implements ViewMatcherInterface
{
    private bool $isPreview = true;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function setMatchingConfig($matchingConfig): void
    {
        if (!is_bool($matchingConfig)) {
            throw new InvalidArgumentException(
                '$matchConfig',
                sprintf(
                    'IsPreview matcher expects true or false value, got a value of %s type',
                    gettype($matchingConfig)
                )
            );
        }

        $this->isPreview = $matchingConfig;
    }

    public function match(View $view): bool
    {
        $isPreview = $view->hasParameter(PreviewController::PREVIEW_PARAMETER_NAME)
            && $view->getParameter(PreviewController::PREVIEW_PARAMETER_NAME);

        return $this->isPreview === $isPreview;
    }
}
