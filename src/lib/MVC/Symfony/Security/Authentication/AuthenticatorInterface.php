<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Security\Authentication;

use Symfony\Component\HttpFoundation\Request;

/**
 * This interface is to be implemented by authenticator classes.
 * Authenticators are meant to be used to run authentication programmatically, i.e. outside the firewall context.
 *
 * @deprecated 4.6.7 this class is deprecated. Symfony Security has received major changes in 5.3, therefore Ibexa DXP relies on authenticator system from now on. Will be removed in 5.0.
 */
interface AuthenticatorInterface
{
    /**
     * Runs authentication against provided request and returns the authenticated security token.
     *
     * This method typically does:
     *  - The authentication by itself (i.e. matching a user)
     *  - User type checks (e.g. check user activation)
     *  - Inject authenticated token in the SecurityContext
     *  - (optional) Trigger SecurityEvents::INTERACTIVE_LOGIN event
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\Security\Core\Authentication\Token\TokenInterface
     *
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException If any authentication issue occured.
     */
    public function authenticate(Request $request);

    /**
     * Performs logout by running configured logout handlers.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logout(Request $request);
}

class_alias(AuthenticatorInterface::class, 'eZ\Publish\Core\MVC\Symfony\Security\Authentication\AuthenticatorInterface');
