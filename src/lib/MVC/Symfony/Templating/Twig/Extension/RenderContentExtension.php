<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface;
use Ibexa\Core\MVC\Symfony\Event\ResolveRenderOptionsEvent;
use Ibexa\Core\MVC\Symfony\Templating\RenderContentStrategy;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
final class RenderContentExtension extends AbstractExtension
{
    /** @var \Ibexa\Core\MVC\Symfony\Templating\RenderContentStrategy */
    private $renderContentStrategy;

    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        RenderContentStrategy $renderContentStrategy,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->renderContentStrategy = $renderContentStrategy;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ibexa_render_content',
                $this->renderContent(...),
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function renderContent(Content|ContentAwareInterface $data, array $options = []): string
    {
        $renderOptions = new RenderOptions($options);
        $event = $this->eventDispatcher->dispatch(
            new ResolveRenderOptionsEvent($renderOptions)
        );

        return $this->renderContentStrategy->render($this->getContent($data), $event->getRenderOptions());
    }

    private function getContent(Content|ContentAwareInterface $data): Content
    {
        if ($data instanceof Content) {
            return $data;
        }

        return $data->getContent();
    }
}
