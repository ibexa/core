<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Notification\Renderer;

use Ibexa\Contracts\Core\Repository\Values\Notification\Notification;
use Ibexa\Core\Notification\Renderer\NotificationRenderer;
use Ibexa\Core\Notification\Renderer\Registry;
use Ibexa\Core\Notification\Renderer\TypedNotificationRendererInterface;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function testGetTypeLabelsReturnsSortedLabelsFromTypedRenderers(): void
    {
        $typedRendererA = new class() implements NotificationRenderer, TypedNotificationRendererInterface {
            public function render(Notification $notification): string
            {
                return 'Rendered A';
            }

            public function generateUrl(Notification $notification): ?string
            {
                return null;
            }

            public function getTypeLabel(): string
            {
                return 'Label A';
            }
        };

        $typedRendererB = new class() implements NotificationRenderer, TypedNotificationRendererInterface {
            public function render(Notification $notification): string
            {
                return 'Rendered B';
            }

            public function generateUrl(Notification $notification): ?string
            {
                return null;
            }

            public function getTypeLabel(): string
            {
                return 'Label B';
            }
        };

        $nonTypedRenderer = $this->createMock(NotificationRenderer::class);

        $registry = new Registry();
        $registry->addRenderer('z_type', $typedRendererB);
        $registry->addRenderer('a_type', $typedRendererA);
        $registry->addRenderer('x_type', $nonTypedRenderer);

        $expected = [
            'a_type' => 'Label A',
            'z_type' => 'Label B',
        ];

        $this->assertSame($expected, $registry->getTypeLabels());
    }
}
