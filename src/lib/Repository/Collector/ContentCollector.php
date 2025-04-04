<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Collector;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Symfony\Contracts\Service\ResetInterface;

final class ContentCollector implements ResetInterface
{
    /** @var array<int, bool> */
    private array $contentMap = [];

    public function collectContent(Content $content): void
    {
        $this->contentMap[$content->getId()] = false;
    }

    /**
     * @return int[]
     */
    public function getCollectedContentIds(): array
    {
        return array_keys($this->contentMap);
    }

    public function reset(): void
    {
        $this->contentMap = [];
    }
}
