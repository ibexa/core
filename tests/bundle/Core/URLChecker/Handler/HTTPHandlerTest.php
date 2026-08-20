<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\URLChecker\Handler;

use Ibexa\Bundle\Core\URLChecker\Handler\HTTPHandler;
use Ibexa\Contracts\Core\Repository\URLService;
use Ibexa\Contracts\Core\Repository\Values\URL\URL;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @covers \Ibexa\Bundle\Core\URLChecker\Handler\HTTPHandler
 */
final class HTTPHandlerTest extends TestCase
{
    private const PARAMETER_NAME = 'url_handler.http.options';

    /** @var \Ibexa\Contracts\Core\Repository\URLService|\PHPUnit\Framework\MockObject\MockObject */
    private $urlService;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configResolver;

    protected function setUp(): void
    {
        $this->urlService = $this->createMock(URLService::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
    }

    public function testGetOptionsDefaults(): void
    {
        $handler = $this->createHandler([]);

        $options = $handler->getOptions();

        self::assertTrue($options['enabled']);
        self::assertSame(10, $options['timeout']);
        self::assertSame(5, $options['connection_timeout']);
        self::assertSame(10, $options['batch_size']);
        self::assertFalse($options['ignore_certificate']);
        self::assertSame('HEAD', $options['method']);
        self::assertTrue($options['fallback_to_get']);
        self::assertNotEmpty($options['user_agent']);
        self::assertArrayHasKey('Accept', $options['headers']);
    }

    public function testGetOptionsUsesInjectedParameterName(): void
    {
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('url_handler.https.options', null, null)
            ->willReturn([]);

        $handler = new HTTPHandler(
            $this->urlService,
            $this->configResolver,
            'url_handler.https.options'
        );

        $handler->getOptions();
    }

    public function testGetOptionsRejectsInvalidMethod(): void
    {
        $handler = $this->createHandler([
            'method' => 'POST',
        ]);

        $this->expectException(InvalidOptionsException::class);

        $handler->getOptions();
    }

    public function testBuildRequestHeaders(): void
    {
        $handler = $this->createHandler([]);

        $headers = $this->invokePrivateMethod($handler, 'buildRequestHeaders', [
            [
                'Accept' => 'text/html',
                'Accept-Language' => 'en',
                'X-Raw-Header: 1',
            ],
        ]);

        self::assertSame(
            [
                'Accept: text/html',
                'Accept-Language: en',
                'X-Raw-Header: 1',
            ],
            $headers
        );
    }

    /**
     * @dataProvider provideDataForTestIsSuccessful
     */
    public function testIsSuccessful(int $statusCode, bool $expected): void
    {
        $handler = $this->createHandler([]);

        self::assertSame(
            $expected,
            $this->invokePrivateMethod($handler, 'isSuccessful', [$statusCode])
        );
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function provideDataForTestIsSuccessful(): iterable
    {
        yield 'curl error' => [0, false];
        yield 'status 199' => [199, false];
        yield 'status 200' => [200, true];
        yield 'status 204' => [204, true];
        yield 'status 299' => [299, true];
        yield 'status 300' => [300, false];
        yield 'status 403' => [403, false];
        yield 'status 404' => [404, false];
        yield 'status 500' => [500, false];
    }

    /**
     * @dataProvider provideDataForTestShouldRetryWithGet
     */
    public function testShouldRetryWithGet(
        int $statusCode,
        string $requestMethod,
        bool $fallbackToGet,
        bool $expected
    ): void {
        $handler = $this->createHandler([]);

        self::assertSame(
            $expected,
            $this->invokePrivateMethod($handler, 'shouldRetryWithGet', [
                $statusCode,
                $requestMethod,
                ['fallback_to_get' => $fallbackToGet],
            ])
        );
    }

    /**
     * @return iterable<string, array{int, string, bool, bool}>
     */
    public static function provideDataForTestShouldRetryWithGet(): iterable
    {
        yield 'HEAD blocked by WAF' => [403, 'HEAD', true, true];
        yield 'HEAD not allowed' => [405, 'HEAD', true, true];
        yield 'HEAD curl error' => [0, 'HEAD', true, true];
        yield 'HEAD succeeded' => [200, 'HEAD', true, false];
        yield 'GET is final' => [403, 'GET', true, false];
        yield 'fallback disabled' => [403, 'HEAD', false, false];
    }

    public function testValidateDoesNothingWhenDisabled(): void
    {
        $handler = $this->createHandler([
            'enabled' => false,
        ]);

        $this->urlService
            ->expects(self::never())
            ->method('updateUrl');

        $handler->validate([
            new URL([
                'id' => 1,
                'url' => 'http://127.0.0.1:1/',
            ]),
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createHandler(array $options): HTTPHandler
    {
        $this->configResolver
            ->method('getParameter')
            ->with(self::PARAMETER_NAME, null, null)
            ->willReturn($options);

        return new HTTPHandler(
            $this->urlService,
            $this->configResolver,
            self::PARAMETER_NAME
        );
    }

    /**
     * @param array<int, mixed> $arguments
     *
     * @return mixed
     */
    private function invokePrivateMethod(HTTPHandler $handler, string $method, array $arguments)
    {
        $reflection = new ReflectionMethod(HTTPHandler::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($handler, $arguments);
    }
}
