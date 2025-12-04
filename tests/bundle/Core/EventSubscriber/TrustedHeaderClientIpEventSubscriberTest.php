<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\EventSubscriber;

use Ibexa\Bundle\Core\EventSubscriber\TrustedHeaderClientIpEventSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final class TrustedHeaderClientIpEventSubscriberTest extends TestCase
{
    private ?string $originalRemoteAddr;

    private const string PROXY_IP = '127.100.100.1';

    private const string REAL_CLIENT_IP = '98.76.123.234';

    /**
     * @param array<mixed> $data
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
    }

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = null;
        Request::setTrustedProxies([], -1);
    }

    protected function tearDown(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddr;
    }

    public function getTrustedHeaderEventSubscriberTestData(): array
    {
        return [
            'default behaviour' => [
                self::REAL_CLIENT_IP,
                self::REAL_CLIENT_IP,
            ],
            'use custom header name with valid value' => [
                self::REAL_CLIENT_IP,
                self::PROXY_IP,
                'X-Custom-Header',
                ['X-Custom-Header' => self::REAL_CLIENT_IP],
            ],
            'use custom header name without valid value' => [
                self::PROXY_IP,
                self::PROXY_IP,
                'X-Custom-Header',
            ],
            'use custom header value without custom header name' => [
                self::PROXY_IP,
                self::PROXY_IP,
                null,
                ['X-Custom-Header' => self::REAL_CLIENT_IP],
            ],
        ];
    }

    public function testTrustedHeaderEventSubscriberWithoutTrustedProxy(): void
    {
        $_SERVER['REMOTE_ADDR'] = self::PROXY_IP;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(
            new TrustedHeaderClientIpEventSubscriber('X-Custom-Header')
        );

        $request = Request::create('/', Request::METHOD_GET, [], [], [], $_SERVER);
        $request->headers->add([
            'X-Custom-Header' => self::REAL_CLIENT_IP,
        ]);

        $event = $eventDispatcher->dispatch(new RequestEvent(
            self::createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ), KernelEvents::REQUEST);

        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $event->getRequest();

        self::assertEquals(self::PROXY_IP, $request->getClientIp());
    }

    /**
     * @dataProvider getTrustedHeaderEventSubscriberTestData
     */
    public function testTrustedHeaderEventSubscriberWithTrustedProxy(
        string $expectedIp,
        string $remoteAddrIp,
        ?string $trustedHeaderName = null,
        array $headers = []
    ): void {
        $_SERVER['REMOTE_ADDR'] = $remoteAddrIp;
        Request::setTrustedProxies(['REMOTE_ADDR'], Request::getTrustedHeaderSet());

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(
            new TrustedHeaderClientIpEventSubscriber($trustedHeaderName)
        );

        $request = Request::create('/', Request::METHOD_GET, [], [], [], ['REMOTE_ADDR' => $remoteAddrIp]);
        $request->headers->add($headers);

        $event = $eventDispatcher->dispatch(new RequestEvent(
            self::createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ), KernelEvents::REQUEST);

        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $event->getRequest();

        self::assertEquals($expectedIp, $request->getClientIp());
    }
}
