<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIElement;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIElement as URIElementMatcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Psr\Log\LoggerInterface;

class RouterURIElementTest extends RouterBaseTestCase
{
    public function matchProvider(): array
    {
        return [
            [SimplifiedRequest::fromUrl('http://example.com'), 'default_sa'],
            [SimplifiedRequest::fromUrl('https://example.com'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('https://example.com/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com//'), 'default_sa'],
            [SimplifiedRequest::fromUrl('https://example.com//'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:8080/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_siteaccess/'), 'first_siteaccess'],
            [SimplifiedRequest::fromUrl('http://example.com/?first_siteaccess'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/?first_sa'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_salt'), 'first_salt'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa.foo'), 'first_sa.foo'],
            [SimplifiedRequest::fromUrl('http://example.com/test'), 'test'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/'), 'test'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/bar/'), 'test'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/bar/first_sa'), 'test'],
            [SimplifiedRequest::fromUrl('http://example.com/default_sa'), 'default_sa'],

            [SimplifiedRequest::fromUrl('http://example.com/first_sa'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa/'), 'first_sa'],
            // Double slashes shouldn't be considered as one
            [SimplifiedRequest::fromUrl('http://example.com//first_sa//'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com///first_sa///test'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com//first_sa//foo/bar'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa/foo'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:82/first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://third_siteaccess/first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('https://first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_sa:81/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess/foo/'), 'foo'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/foo/'), 'foo'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/foo/'), 'foo'],

            [SimplifiedRequest::fromUrl('http://example.com/second_sa'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa?param1=foo'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa/foo/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:82/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:83/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/second_sa/'), 'second_sa'],
        ];
    }

    public function testGetName()
    {
        $matcher = new URIElementMatcher([]);
        self::assertSame('uri:element', $matcher->getName());
    }

    /**
     * @param string $uri
     * @param string $expectedFixedUpURI
     *
     * @dataProvider analyseProvider
     */
    public function testAnalyseURI($uri, $expectedFixedUpURI)
    {
        $matcher = new URIElementMatcher([1]);
        $matcher->setRequest(
            new SimplifiedRequest('http', '', 80, $uri)
        );
        self::assertSame($expectedFixedUpURI, $matcher->analyseURI($uri));
    }

    /**
     * @param string $fullUri
     * @param string $linkUri
     *
     * @dataProvider analyseProvider
     */
    public function testAnalyseLink($fullUri, $linkUri)
    {
        $matcher = new URIElementMatcher([1]);
        $matcher->setRequest(
            new SimplifiedRequest('http', '', 80, $fullUri)
        );
        self::assertSame($fullUri, $matcher->analyseLink($linkUri));
    }

    public function analyseProvider()
    {
        return [
            ['/my_siteaccess/foo/bar', '/foo/bar'],
            ['/vive/le/sucre', '/le/sucre'],
        ];
    }

    /**
     * @dataProvider reverseMatchProvider
     */
    public function testReverseMatch($siteAccessName, $originalPathinfo)
    {
        $matcher = new URIElementMatcher([1]);
        $matcher->setRequest(new SimplifiedRequest('http', '', 80, $originalPathinfo));
        $result = $matcher->reverseMatch($siteAccessName);
        self::assertInstanceOf(URIElement::class, $result);
        self::assertSame("/{$siteAccessName}{$originalPathinfo}", $result->getRequest()->getPathInfo());
        self::assertSame("/$siteAccessName/some/linked/uri", $result->analyseLink('/some/linked/uri'));
        self::assertSame('/foo/bar/baz', $result->analyseURI("/$siteAccessName/foo/bar/baz"));
    }

    public function reverseMatchProvider()
    {
        return [
            ['something', '/foo/bar'],
            ['something', '/'],
            ['some_thing', '/foo/bar'],
            ['another_siteaccess', '/foo/bar'],
            ['another_siteaccess_again_dont_tell_me', '/foo/bar'],
        ];
    }

    public function testSerialize()
    {
        $matcher = new URIElementMatcher([1]);
        $matcher->setRequest(new SimplifiedRequest('http', '', 80, '/foo/bar'));
        $sa = new SiteAccess('test', 'test', $matcher);
        $serializedSA1 = serialize($sa);

        $matcher->setRequest(new SimplifiedRequest('http', '', 80, '/foo/bar/baz'));
        $serializedSA2 = serialize($sa);

        self::assertSame($serializedSA1, $serializedSA2);
    }

    protected function createRouter(): Router
    {
        return new Router(
            $this->matcherBuilder,
            $this->createMock(LoggerInterface::class),
            'default_sa',
            [
                'URIElement' => [
                    'value' => 1,
                ],
                'Map\\URI' => [
                    'first_sa' => 'first_sa',
                    'second_sa' => 'second_sa',
                ],
                'Map\\Host' => [
                    'first_sa' => 'first_sa',
                    'first_siteaccess' => 'first_sa',
                ],
            ],
            $this->siteAccessProvider
        );
    }

    /**
     * @return \Ibexa\Tests\Core\MVC\Symfony\SiteAccess\SiteAccessSetting[]
     */
    public function getSiteAccessProviderSettings(): array
    {
        return [
            new SiteAccessSetting('first_sa', true),
            new SiteAccessSetting('second_sa', true),
            new SiteAccessSetting('first_siteaccess', true),
            new SiteAccessSetting('first_salt', true),
            new SiteAccessSetting('first_sa.foo', true),
            new SiteAccessSetting('test', true),
            new SiteAccessSetting('foo', true),
            new SiteAccessSetting(self::DEFAULT_SA_NAME, true),
        ];
    }
}
