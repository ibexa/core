<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Fragment;

use Ibexa\Bundle\Core\EventListener\ViewControllerListener;
use Ibexa\Core\MVC\Symfony\Templating\Exception\InvalidResponseException;
use Ibexa\Core\MVC\Symfony\View\Renderer\TemplateRenderer;
use Ibexa\Core\MVC\Symfony\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactoryInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class DirectFragmentRenderer extends InlineFragmentRenderer implements FragmentRendererInterface
{
    public const NAME = 'direct';

    /** @var KernelInterface */
    protected $kernel;

    /** @var ViewControllerListener */
    protected $controllerListener;

    /** @var ControllerResolverInterface */
    protected $controllerResolver;

    /** @var ArgumentMetadataFactoryInterface */
    protected $argumentMetadataFactory;

    /** @var ValueResolverInterface */
    protected $argumentValueResolver;

    /** @var TemplateRenderer */
    protected $viewTemplateRenderer;

    public function __construct(
        FragmentRendererInterface $innerRenderer,
        KernelInterface $kernel,
        ViewControllerListener $controllerListener,
        ControllerResolverInterface $controllerResolver,
        ArgumentMetadataFactoryInterface $argumentMetadataFactory,
        ValueResolverInterface $argumentValueResolver,
        TemplateRenderer $viewTemplateRenderer
    ) {
        $this->innerRenderer = $innerRenderer;
        $this->kernel = $kernel;
        $this->controllerListener = $controllerListener;
        $this->controllerResolver = $controllerResolver;
        $this->argumentMetadataFactory = $argumentMetadataFactory;
        $this->argumentValueResolver = $argumentValueResolver;
        $this->viewTemplateRenderer = $viewTemplateRenderer;
    }

    protected function getControllerEvent(Request $request): ControllerEvent
    {
        $controller = $this->controllerResolver->getController($request);

        $this->controllerListener->getController(
            $event = new ControllerEvent(
                $this->kernel,
                $controller,
                $request,
                HttpKernelInterface::SUB_REQUEST
            )
        );

        return $event;
    }

    protected function getController(ControllerEvent $event): callable
    {
        return $this->controllerResolver->getController($event->getRequest());
    }

    protected function getArguments(
        callable $controller,
        ControllerEvent $event
    ): array {
        $argumentsMetadata = $this->argumentMetadataFactory->createArgumentMetadata($controller);

        $arguments = [];
        foreach ($argumentsMetadata as $argumentMetadata) {
            // Single-value generator
            $valueGenerator = $this->argumentValueResolver->resolve($event->getRequest(), $argumentMetadata);
            foreach ($valueGenerator as $value) {
                $arguments[$argumentMetadata->getName()] = $value;
                break;
            }
        }

        return $arguments;
    }

    protected function getFragmentUri(
        ControllerReference $reference,
        Request $request
    ): string {
        $simplifiedReference = clone $reference;
        $simplifiedReference->attributes = [
            '_format' => $reference->attributes['_format'] ?? null,
            '_locale' => $reference->attributes['_locale'] ?? null,
        ];

        return $this->generateFragmentUri($simplifiedReference, $request, false, false);
    }

    /**
     * @param string|ControllerReference $uri
     *
     * @throws InvalidResponseException
     */
    public function render(
        $uri,
        Request $request,
        array $options = []
    ): Response {
        $controllerReference = null;
        if ($uri instanceof ControllerReference) {
            $controllerReference = $uri;
            $uri = $this->getFragmentUri($controllerReference, $request);
        }

        $subRequest = $this->createSubRequest($uri, $request);
        $subRequest->attributes->set('siteaccess', $request->attributes->get('siteaccess'));

        if (null !== $controllerReference) {
            $subRequest->attributes->add($controllerReference->attributes);
            $subRequest->attributes->set('_controller', $controllerReference->controller);
        }

        $event = $this->getControllerEvent($subRequest);
        $controller = $this->getController($event);
        $arguments = $this->getArguments($controller, $event);

        $response = $controller(...array_values($arguments));

        if ($response instanceof Response) {
            return $response;
        } elseif ($response instanceof View) {
            return new Response($this->viewTemplateRenderer->render($response));
        } elseif (is_string($response)) {
            return new Response($response);
        }

        throw new InvalidResponseException(
            sprintf('Unsupported type (%s)', is_object($response) ? get_class($response) : gettype($response))
        );
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
