<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map\Port as PortMatcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Psr\Log\LoggerInterface;

class RouterSpecialPortsTest extends RouterBaseTestCase
{
    public function matchProvider(): array
    {
        return [
            [SimplifiedRequest::fromUrl('http://example.com'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('https://example.com'), 'fourth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('https://example.com/'), 'fourth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com//'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('https://example.com//'), 'fourth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:8080/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_siteaccess/'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/?first_siteaccess'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/?first_sa'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_salt'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa.foo'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/bar/'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/bar/first_sa'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/default_sa'), 'fifth_sa'],

            [SimplifiedRequest::fromUrl('http://example.com/first_sa'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa/'), 'first_sa'],
            // Shouldn't match "first_sa" because of double slash
            [SimplifiedRequest::fromUrl('http://example.com//first_sa/'), 'fifth_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa//'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa///test'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa/foo'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa/foo/bar'), 'first_sa'],
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

            [SimplifiedRequest::fromUrl('http://example.com/second_sa'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa?param1=foo'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa/foo/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:82/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:83/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/second_sa/'), 'second_sa'],

            [SimplifiedRequest::fromUrl('http://example.com:81/'), 'third_sa'],
            [SimplifiedRequest::fromUrl('https://example.com:81/'), 'third_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:81/foo'), 'third_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:81/foo/bar'), 'third_sa'],

            [SimplifiedRequest::fromUrl('http://example.com:82/'), 'fourth_sa'],
            [SimplifiedRequest::fromUrl('https://example.com:82/'), 'fourth_sa'],
            [SimplifiedRequest::fromUrl('https://example.com:82/foo'), 'fourth_sa'],
        ];
    }

    public function testGetName()
    {
        $matcher = new PortMatcher(['port' => '8080', 'scheme' => 'http'], []);
        self::assertSame('port', $matcher->getName());
    }

    protected function createRouter(): Router
    {
        return new Router(
            $this->matcherBuilder,
            $this->createMock(LoggerInterface::class),
            'default_sa',
            [
                'Map\\URI' => [
                    'first_sa' => 'first_sa',
                    'second_sa' => 'second_sa',
                ],
                'Map\\Host' => [
                    'first_sa' => 'first_sa',
                    'first_siteaccess' => 'first_sa',
                    'third_siteaccess' => 'third_sa',
                ],
                'Map\\Port' => [
                    80 => 'fifth_sa',
                    81 => 'third_sa',
                    82 => 'fourth_sa',
                    83 => 'first_sa',
                    85 => 'first_sa',
                    443 => 'fourth_sa',
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
            new SiteAccessSetting(self::DEFAULT_SA_NAME, true),
        ];
    }
}
