<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

/**
 * Converts urls between two decorators.
 */
class UrlRedecorator implements UrlRedecoratorInterface
{
    private UrlDecorator $sourceDecorator;

    private UrlDecorator $targetDecorator;

    public function __construct(
        UrlDecorator $sourceDecorator,
        UrlDecorator $targetDecorator
    ) {
        $this->sourceDecorator = $sourceDecorator;
        $this->targetDecorator = $targetDecorator;
    }

    public function redecorateFromSource(string $uri): string
    {
        return $this->targetDecorator->decorate(
            $this->sourceDecorator->undecorate($uri)
        );
    }

    public function redecorateFromTarget(string $uri): string
    {
        return $this->sourceDecorator->decorate(
            $this->targetDecorator->undecorate($uri)
        );
    }
}
