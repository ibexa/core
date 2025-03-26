<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\MVC\Templating\RenderStrategy;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\MVC\Symfony\Event\ResolveRenderOptionsEvent;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
final class RenderExtension extends AbstractExtension
{
    /** @var \Ibexa\Contracts\Core\MVC\Templating\RenderStrategy */
    private $renderStrategy;

    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        RenderStrategy $renderStrategy,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->renderStrategy = $renderStrategy;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ibexa_render',
                $this->render(...),
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(ValueObject|ContentAwareInterface $object, array $options = []): string
    {
        if ($object instanceof ContentAwareInterface) {
            $object = $object->getContent();
        }

        if (!$this->renderStrategy->supports($object)) {
            throw new InvalidArgumentException(
                'valueObject',
                sprintf('%s is not supported.', get_class($object))
            );
        }

        $renderOptions = new RenderOptions($options);
        $event = $this->eventDispatcher->dispatch(
            new ResolveRenderOptionsEvent($renderOptions)
        );

        return $this->renderStrategy->render($object, $event->getRenderOptions());
    }
}
