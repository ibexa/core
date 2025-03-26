<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\MVC\Templating\RenderStrategy;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface;
use Ibexa\Contracts\Core\Repository\Values\Setting\Setting;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\MVC\Symfony\Event\ResolveRenderOptionsEvent;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\RenderExtension;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Test\IntegrationTestCase;

final class RenderExtensionTest extends IntegrationTestCase
{
    /** @var \Ibexa\Contracts\Core\MVC\Templating\RenderStrategy&\PHPUnit\Framework\MockObject\MockObject */
    private RenderStrategy $renderStrategy;

    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface&\PHPUnit\Framework\MockObject\MockObject */
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->renderStrategy = $this->createMock(RenderStrategy::class);
        $this->renderStrategy->method('supports')->willReturnCallback(
            static fn (ValueObject $vo): bool => !$vo instanceof Setting
        );
        $this->renderStrategy->method('render')->willReturnCallback(
            static function (Content $valueObject): ?string {
                return $valueObject->getName();
            }
        );

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher->method('dispatch')->willReturn(
            new ResolveRenderOptionsEvent(new RenderOptions())
        );
    }

    protected function getExtensions(): array
    {
        return [
            new RenderExtension(
                $this->renderStrategy,
                $this->eventDispatcher
            ),
        ];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/_fixtures/render';
    }

    public function getExampleContent(string $name): Content
    {
        $content = $this->createMock(Content::class);
        $content->method('getName')->willReturn($name);

        return $content;
    }

    public function getExampleContentAware(string $name): ContentAwareInterface
    {
        $contentAware = $this->createMock(ContentAwareInterface::class);
        $contentAware->method('getContent')->willReturn($this->getExampleContent($name));

        return $contentAware;
    }

    public function getExampleUnsupportedValueObject(): Setting
    {
        return new Setting();
    }
}
