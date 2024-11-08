<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map\URI as URIMapMatcher;
use PHPUnit\Framework\TestCase;

class RouterMapURITest extends TestCase
{
    /**
     * @param array  $config
     * @param string $pathinfo
     * @param string $expectedMapKey
     *
     * @dataProvider setRequestProvider
     */
    public function testSetGetRequest($config, $pathinfo, $expectedMapKey)
    {
        $request = new SimplifiedRequest('http', '', 80, $pathinfo);
        $matcher = new URIMapMatcher($config);
        $matcher->setRequest($request);
        self::assertSame($request, $matcher->getRequest());
        self::assertSame($expectedMapKey, $matcher->getMapKey());
    }

    /**
     * @param string $uri
     * @param string $expectedFixedUpURI
     *
     * @dataProvider fixupURIProvider
     */
    public function testAnalyseURI($uri, $expectedFixedUpURI)
    {
        $matcher = new URIMapMatcher([]);
        $matcher->setRequest(
            new SimplifiedRequest('http', '', 80, $uri)
        );
        self::assertSame($expectedFixedUpURI, $matcher->analyseURI($uri));
        // Unserialized matcher should have the same behavior
        $unserializedMatcher = unserialize(serialize($matcher));
        self::assertSame($expectedFixedUpURI, $unserializedMatcher->analyseURI($uri));
    }

    /**
     * @param string $fullUri
     * @param string $linkUri
     *
     * @dataProvider fixupURIProvider
     */
    public function testAnalyseLink($fullUri, $linkUri)
    {
        $matcher = new URIMapMatcher([]);
        $matcher->setRequest(
            new SimplifiedRequest('http', '', 80, $fullUri)
        );
        self::assertSame($fullUri, $matcher->analyseLink($linkUri));
        // Unserialized matcher should have the same behavior
        $unserializedMatcher = unserialize(serialize($matcher));
        self::assertSame($fullUri, $unserializedMatcher->analyseLink($linkUri));
    }

    public function setRequestProvider()
    {
        return [
            [['foo' => 'bar'], '/bar/baz', 'bar'],
            [['foo' => 'Äpfel'], '/%C3%84pfel/foo', 'Äpfel'],
        ];
    }

    public function fixupURIProvider()
    {
        return [
            ['/foo', '/'],
            ['/Äpfel', '/'],
            ['/my_siteaccess/foo/bar', '/foo/bar'],
            ['/foo/foo/bar', '/foo/bar'],
            ['/foo/foo/bar?something=foo&bar=toto', '/foo/bar?something=foo&bar=toto'],
            ['/vive/le/sucre', '/le/sucre'],
            ['/ibexa_demo_site/some/thing?foo=ibexa_demo_site&bar=toto', '/some/thing?foo=ibexa_demo_site&bar=toto'],
        ];
    }

    public function testReverseMatchFail()
    {
        $config = ['foo' => 'bar'];
        $matcher = new URIMapMatcher($config);
        self::assertNull($matcher->reverseMatch('non_existent'));
    }

    public function testReverseMatch()
    {
        $config = [
            'some_uri' => 'some_siteaccess',
            'something_else' => 'another_siteaccess',
            'toutouyoutou' => 'ibexa_demo_site',
        ];
        $request = new SimplifiedRequest('http', '', 80, '/foo');
        $matcher = new URIMapMatcher($config);
        $matcher->setRequest($request);

        $result = $matcher->reverseMatch('ibexa_demo_site');
        self::assertInstanceOf(URIMapMatcher::class, $result);
        self::assertSame($request, $matcher->getRequest());
        self::assertSame('toutouyoutou', $result->getMapKey());
        self::assertSame('/toutouyoutou/foo', $result->getRequest()->getPathInfo());
    }
}
