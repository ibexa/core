<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIText;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIText as URITextMatcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Psr\Log\LoggerInterface;

class RouterURITextTest extends RouterBaseTestCase
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
            [SimplifiedRequest::fromUrl('http://example.com/first_siteaccess/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/?first_siteaccess'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/?first_sa'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_salt'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa.foo'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/bar/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/bar/first_sa'), 'default_sa'],
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
            [SimplifiedRequest::fromUrl('http://first_siteaccess/foo/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/foo/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/foo/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess/foobar/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess//foobar/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess//footestbar/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess/footestbar/'), 'test'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess/footestbar/foobazbar/'), 'test'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/footestbar/'), 'test'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/footestbar/'), 'test'],

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
        $matcher = new URITextMatcher([], []);
        self::assertSame('uri:text', $matcher->getName());
    }

    public function testAnalyseURI()
    {
        $siteAccessURI = '/footestbar';
        $semanticURI = '/something/hoho';
        $matcher = new URITextMatcher(
            [
                'prefix' => 'foo',
                'suffix' => 'bar',
            ]
        );
        $matcher->setRequest(SimplifiedRequest::fromUrl('http://phoenix-rises.fm/footestbar/blabla'));

        self::assertSame($semanticURI, $matcher->analyseURI($siteAccessURI . $semanticURI));
    }

    public function testAnalyseLink()
    {
        $siteAccessURI = '/footestbar';
        $semanticURI = '/something/hoho';
        $matcher = new URITextMatcher(
            [
                'prefix' => 'foo',
                'suffix' => 'bar',
            ]
        );
        $matcher->setRequest(SimplifiedRequest::fromUrl('http://phoenix-rises.fm/footestbar/blabla'));

        self::assertSame($siteAccessURI . $semanticURI, $matcher->analyseLink($semanticURI));
    }

    public function testReverseMatch()
    {
        $semanticURI = '/hihi/hoho';
        $matcher = new URITextMatcher(
            [
                'prefix' => 'foo',
                'suffix' => 'bar',
            ]
        );
        $matcher->setRequest(new SimplifiedRequest('http', '', 80, $semanticURI));

        $result = $matcher->reverseMatch('something');
        self::assertInstanceOf(URIText::class, $result);
        $request = $result->getRequest();
        self::assertInstanceOf(SimplifiedRequest::class, $request);
        self::assertSame("/foosomethingbar{$semanticURI}", $request->getPathInfo());
    }

    protected function createRouter(): Router
    {
        return new Router(
            $this->matcherBuilder,
            $this->createMock(LoggerInterface::class),
            'default_sa',
            [
                'URIText' => [
                    'prefix' => 'foo',
                    'suffix' => 'bar',
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
            new SiteAccessSetting('third_sa', true),
            new SiteAccessSetting('fourth_sa', true),
            new SiteAccessSetting('fifth_sa', true),
            new SiteAccessSetting('test', true),
            new SiteAccessSetting(self::DEFAULT_SA_NAME, true),
        ];
    }
}
