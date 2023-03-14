<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\MVC\Symfony\Security\Authentication;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Security\Authentication\DefaultAuthenticationSuccessHandler;
use Ibexa\Core\MVC\Symfony\Security\HttpUtils;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DefaultAuthenticationSuccessHandlerTest extends TestCase
{
    public function testSetConfigResolver()
    {
        $siteAccess = new SiteAccess(
            'test',
            SiteAccess::DEFAULT_MATCHING_TYPE,
            $this->createMock(Matcher::class)
        );
        $httpUtils = new HttpUtils();
        $httpUtils->setSiteAccess($siteAccess);
        $successHandler = new DefaultAuthenticationSuccessHandler($httpUtils, []);
        $successHandler->setFirewallName('test_firewall');

        $refHandler = new ReflectionObject($successHandler);
        $refOptions = $refHandler->getProperty('options');
        $refOptions->setAccessible(true);
        $options = $refOptions->getValue($successHandler);
        $this->assertSame('/', $options['default_target_path']);

        $defaultPage = '/foo/bar';
        $configResolver = $this->createMock(ConfigResolverInterface::class);
        $configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('default_page')
            ->will($this->returnValue($defaultPage));
        $successHandler->setConfigResolver($configResolver);
        $successHandler->setEventDispatcher($this->createMock(EventDispatcherInterface::class));

        $request = $this->createMock(Request::class);
        $request
            ->method('getSession')
            ->willReturn($this->createMock(Session::class));

        $request
            ->method('getUriForPath')
            ->willReturn($defaultPage);

        $successHandler->onAuthenticationSuccess($request, $this->createMock(TokenInterface::class));
        $options = $refOptions->getValue($successHandler);
        $this->assertSame($defaultPage, $options['default_target_path']);
    }
}

class_alias(DefaultAuthenticationSuccessHandlerTest::class, 'eZ\Publish\Core\MVC\Symfony\Security\Tests\Authentication\DefaultAuthenticationSuccessHandlerTest');
