<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Controller;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class Controller implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns value for $parameterName and fallbacks to $defaultValue if not defined.
     */
    public function getParameter(string $parameterName, mixed $defaultValue = null): mixed
    {
        if ($this->getConfigResolver()->hasParameter($parameterName)) {
            return $this->getConfigResolver()->getParameter($parameterName);
        }

        return $defaultValue;
    }

    /**
     * Checks if $parameterName is defined.
     */
    public function hasParameter(string $parameterName): bool
    {
        return $this->getConfigResolver()->hasParameter($parameterName);
    }

    /**
     * @return \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface
     */
    public function getConfigResolver(): ConfigResolverInterface
    {
        return $this->container->get('ibexa.config.resolver');
    }

    /**
     * Renders a view.
     *
     * @param string $view The view name
     * @param array $parameters An array of parameters to pass to the view
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render(string $view, array $parameters = [], Response $response = null)
    {
        if (!isset($response)) {
            $response = new Response();
        }

        $response->setContent($this->getTemplateEngine()->render($view, $parameters));

        return $response;
    }

    public function getTemplateEngine(): EngineInterface
    {
        return $this->container->get('templating');
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->container->get('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Repository
     */
    public function getRepository(): Repository
    {
        return $this->container->get('ibexa.api.repository');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest(): Request
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * Checks if the current user has granted access to the provided attribute.
     *
     * @param \Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute $attribute
     *
     * @return bool
     */
    public function isGranted(AuthorizationAttribute $attribute): bool
    {
        return $this->container->get('security.authorization_checker')->isGranted($attribute);
    }

    public static function getSubscribedServices(): array
    {
        return [
            'logger' => '?' . LoggerInterface::class,
            'templating' => EngineInterface::class,
            'ibexa.config.resolver' => ConfigResolverInterface::class,
            'ibexa.api.repository' => Repository::class,
            'request_stack' => RequestStack::class,
            'event_dispatcher' => EventDispatcher::class,
            'security.authorization_checker' => AuthorizationCheckerInterface::class,
        ];
    }
}
