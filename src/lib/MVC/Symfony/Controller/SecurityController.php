<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Controller;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\View\LoginFormView;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

class SecurityController
{
    /** @var Environment */
    protected $templateEngine;

    /** @var ConfigResolverInterface */
    protected $configResolver;

    /** @var AuthenticationUtils */
    protected $authenticationUtils;

    public function __construct(
        Environment $templateEngine,
        ConfigResolverInterface $configResolver,
        AuthenticationUtils $authenticationUtils
    ) {
        $this->templateEngine = $templateEngine;
        $this->configResolver = $configResolver;
        $this->authenticationUtils = $authenticationUtils;
    }

    public function loginAction()
    {
        $view = new LoginFormView($this->configResolver->getParameter('security.login_template'));
        $view->setLastUsername($this->authenticationUtils->getLastUsername());
        $view->setLastAuthenticationError($this->authenticationUtils->getLastAuthenticationError());
        $view->addParameters([
            'layout' => $this->configResolver->getParameter('security.base_layout'),
        ]);

        return $view;
    }
}
