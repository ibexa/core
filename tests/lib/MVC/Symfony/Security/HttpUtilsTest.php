<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Security;

use Ibexa\Core\MVC\Symfony\Security\HttpUtils;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HttpUtilsTest extends TestCase
{
    /**
     * @dataProvider generateUriStandardProvider
     */
    public function testGenerateUriStandard(
        $uri,
        $isUriRouteName,
        $expected
    ) {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $httpUtils = new HttpUtils($urlGenerator);
        $httpUtils->setSiteAccess(new SiteAccess('test'));
        $request = Request::create('http://ezpublish.dev/');
        $request->attributes->set('siteaccess', new SiteAccess('test'));
        $requestAttributes = ['foo' => 'bar', 'some' => 'thing'];
        $request->attributes->add($requestAttributes);

        if ($isUriRouteName) {
            $urlGenerator
                ->expects(self::once())
                ->method('generate')
                ->with($uri, $requestAttributes, UrlGeneratorInterface::ABSOLUTE_URL)
                ->will(self::returnValue($expected . '?' . http_build_query($requestAttributes)));
        }

        self::assertSame($expected, $httpUtils->generateUri($request, $uri));
    }

    public function generateUriStandardProvider()
    {
        return [
            ['http://localhost/foo/bar', false, 'http://localhost/foo/bar'],
            ['http://localhost/foo/bar?some=thing&toto=tata', false, 'http://localhost/foo/bar?some=thing&toto=tata'],
            ['/foo/bar?some=thing&toto=tata', false, 'http://ezpublish.dev/foo/bar?some=thing&toto=tata'],
            ['/foo/bar', false, 'http://ezpublish.dev/foo/bar'],
            ['some_route_name', true, 'http://ezpublish.dev/some/route'],
        ];
    }

    /**
     * @dataProvider generateUriProvider
     */
    public function testGenerateUri(
        $uri,
        $isUriRouteName,
        $siteAccessUri,
        $expected
    ) {
        $siteAccess = new SiteAccess('test', 'test');
        if ($uri[0] === '/') {
            $matcher = $this->createMock(SiteAccess\URILexer::class);
            $matcher
                ->expects(self::once())
                ->method('analyseLink')
                ->with($uri)
                ->will(self::returnValue($siteAccessUri . $uri));
            $siteAccess->matcher = $matcher;
        }

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $httpUtils = new HttpUtils($urlGenerator);
        $httpUtils->setSiteAccess($siteAccess);
        $request = Request::create('http://ezpublish.dev/');
        $request->attributes->set('siteaccess', $siteAccess);
        $requestAttributes = ['foo' => 'bar', 'some' => 'thing'];
        $request->attributes->add($requestAttributes);

        if ($isUriRouteName) {
            $urlGenerator
                ->expects(self::once())
                ->method('generate')
                ->with($uri, $requestAttributes, UrlGeneratorInterface::ABSOLUTE_URL)
                ->will(self::returnValue($expected . '?' . http_build_query($requestAttributes)));
        }

        $res = $httpUtils->generateUri($request, $uri);
        self::assertSame($expected, $res);
    }

    public function generateUriProvider()
    {
        return [
            ['http://localhost/foo/bar', false, null, 'http://localhost/foo/bar'],
            ['http://localhost/foo/bar?some=thing&toto=tata', false, null, 'http://localhost/foo/bar?some=thing&toto=tata'],
            ['/foo/bar?some=thing&toto=tata', false, '/test_access', 'http://ezpublish.dev/test_access/foo/bar?some=thing&toto=tata'],
            ['/foo/bar', false, '/blabla', 'http://ezpublish.dev/blabla/foo/bar'],
            ['some_route_name', true, null, 'http://ezpublish.dev/some/route'],
        ];
    }

    public function testCheckRequestPathStandard()
    {
        $httpUtils = new HttpUtils();
        $httpUtils->setSiteAccess(new SiteAccess('test'));
        $request = Request::create('http://ezpublish.dev/foo/bar');
        self::assertTrue($httpUtils->checkRequestPath($request, '/foo/bar'));
    }

    /**
     * @dataProvider checkRequestPathProvider
     */
    public function testCheckRequestPath(
        $path,
        $siteAccessUri,
        $requestUri,
        $expected
    ) {
        $siteAccess = new SiteAccess('test', 'test');
        if ($siteAccessUri !== null) {
            $matcher = $this->createMock(SiteAccess\URILexer::class);
            $matcher
                ->expects(self::once())
                ->method('analyseLink')
                ->with($path)
                ->will(self::returnValue($siteAccessUri . $path));
            $siteAccess->matcher = $matcher;
        }

        $httpUtils = new HttpUtils();
        $httpUtils->setSiteAccess($siteAccess);
        $request = Request::create($requestUri);
        self::assertSame($expected, $httpUtils->checkRequestPath($request, $path));
    }

    public function checkRequestPathProvider()
    {
        return [
            ['/foo/bar', null, 'http://localhost/foo/bar', true],
            ['/foo', null, 'http://localhost/foo/bar', false],
            ['/foo/bar', null, 'http://localhost/foo/bar?some=thing&toto=tata', true],
            ['/foo/bar', '/test_access', 'http://ezpublish.dev/test_access/foo/bar?some=thing&toto=tata', true],
            ['/foo', '/test_access', 'http://ezpublish.dev/test_access/foo/bar?some=thing&toto=tata', false],
            ['/foo/bar', '/blabla', 'http://ezpublish.dev/blabla/foo/bar', true],
        ];
    }
}
