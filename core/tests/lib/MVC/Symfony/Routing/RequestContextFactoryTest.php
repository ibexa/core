<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Routing;

use Ibexa\Core\MVC\Symfony\Routing\RequestContextFactory;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Routing\RequestContextFactory
 */
final class RequestContextFactoryTest extends TestCase
{
    /**
     * @dataProvider getDataForTestGetContextBySimplifiedRequest
     */
    public function testGetContextBySimplifiedRequest(
        RequestContext $requestContext,
        SimplifiedRequest $simplifiedRequest,
        RequestContext $expectedRequestContext,
    ): void {
        $contextFactory = new RequestContextFactory($requestContext);
        $context = $contextFactory->getContextBySimplifiedRequest($simplifiedRequest);

        // expect cloned object
        self::assertNotSame($requestContext, $context);

        self::assertEquals($expectedRequestContext, $context);
    }

    /**
     * @return iterable<
     *      string,
     *      array{
     *          \Symfony\Component\Routing\RequestContext,
     *          \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest,
     *          \Symfony\Component\Routing\RequestContext
     * }>
     */
    public static function getDataForTestGetContextBySimplifiedRequest(): iterable
    {
        yield 'fully populated HTTP SimplifiedRequest' => [
            new RequestContext(),
            new SimplifiedRequest(
                [
                    'scheme' => 'http',
                    'host' => 'localhost',
                    'port' => '8080',
                    'pathinfo' => 'foo',
                ]
            ),
            new RequestContext('', 'GET', 'localhost', 'http', 8080, 443, 'foo'),
        ];

        yield 'fully populated HTTPS SimplifiedRequest' => [
            new RequestContext(),
            new SimplifiedRequest(
                [
                    'scheme' => 'https',
                    'host' => 'localhost',
                    'port' => '8443',
                    'pathinfo' => 'foo',
                ]
            ),
            new RequestContext('', 'GET', 'localhost', 'https', 80, 8443, 'foo'),
        ];
    }
}
