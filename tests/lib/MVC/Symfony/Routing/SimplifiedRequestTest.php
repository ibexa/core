<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Routing;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest
 */
class SimplifiedRequestTest extends TestCase
{
    /**
     * @param string $url
     * @param SimplifiedRequest $expectedRequest
     *
     * @dataProvider fromUrlProvider
     */
    public function testFromUrl(
        $url,
        $expectedRequest
    ) {
        self::assertEquals(
            $expectedRequest,
            SimplifiedRequest::fromUrl($url)
        );
    }

    public function testStrictGetters(): void
    {
        $headers = ['Cookie' => ['abc', 'def']];
        $languages = ['en', 'pl'];
        $url = 'https://host.invalid:8080/foo?param=bar&param2=bar2';

        $request = SimplifiedRequest::fromUrl($url);
        $request->setHeaders($headers);
        $request->setLanguages($languages);

        self::assertSame($headers['Cookie'], $request->getHeader('Cookie'));
        self::assertSame($headers, $request->getHeaders());
        self::assertSame($languages, $request->getLanguages());
        self::assertSame('https', $request->getScheme());
        self::assertSame('host.invalid', $request->getHost());
        self::assertSame(8080, $request->getPort());
        self::assertSame('/foo', $request->getPathInfo());
        self::assertSame(['param' => 'bar', 'param2' => 'bar2'], $request->getQueryParams());
    }

    public function fromUrlProvider()
    {
        return [
            [
                'http://www.example.com/foo/bar',
                new SimplifiedRequest(
                    [
                        'scheme' => 'http',
                        'host' => 'www.example.com',
                        'pathinfo' => '/foo/bar',
                    ]
                ),
            ],
            [
                'https://www.example.com/',
                new SimplifiedRequest(
                    [
                        'scheme' => 'https',
                        'host' => 'www.example.com',
                        'pathinfo' => '/',
                    ]
                ),
            ],
            [
                'http://www.example.com/foo?param=value&this=that',
                new SimplifiedRequest(
                    [
                        'scheme' => 'http',
                        'host' => 'www.example.com',
                        'pathinfo' => '/foo',
                        'queryParams' => ['param' => 'value', 'this' => 'that'],
                    ]
                ),
            ],
        ];
    }
}
