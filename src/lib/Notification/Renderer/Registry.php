<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Notification\Renderer;

class Registry
{
    /** @var NotificationRenderer[] */
    protected $registry = [];

    /**
     * @param string $alias
     * @param NotificationRenderer $notificationRenderer
     */
    public function addRenderer(
        string $alias,
        NotificationRenderer $notificationRenderer
    ): void {
        $this->registry[$alias] = $notificationRenderer;
    }

    /**
     * @param string $alias
     *
     * @return NotificationRenderer
     */
    public function getRenderer(string $alias): NotificationRenderer
    {
        return $this->registry[$alias];
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function hasRenderer(string $alias): bool
    {
        return isset($this->registry[$alias]);
    }

    /**
     * @return array<string, string>
     */
    public function getTypeLabels(): array
    {
        $labels = [];
        foreach ($this->registry as $type => $renderer) {
            if ($renderer instanceof TypedNotificationRendererInterface) {
                $labels[$type] = $renderer->getTypeLabel();
            }
        }
        ksort($labels);

        return $labels;
    }
}
