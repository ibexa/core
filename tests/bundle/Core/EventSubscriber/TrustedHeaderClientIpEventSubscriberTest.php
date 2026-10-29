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

    private const REAL_CLIENT_IP = '98.76.123.234';

    // framework.trusted_headers default
    private const TRUSTED_HEADER_SET = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO;

    /**
     * @param array<mixed> $data
     */
    public function __construct(
        ?string $name = null,
        array $data = [],
        string $dataName = ''
    ) {
        parent::__construct($name, $data, $dataName);

        $this->originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
    }

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = null;
        Request::setTrustedProxies([], self::TRUSTED_HEADER_SET);
    }

    protected function tearDown(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddr;
    }

    public function getTrustedHeaderEventSubscriberTestData(): array
    {
        return [
            'request from random client received on non-Upsun platform' => [
                false,
                [],
                [],
            ],
            'request from random client, forging Client-Cdn received on non-Upsun platform' => [
                false,
                ['Client-Cdn' => 'fastly'],
                [],
            ],
            'request from random client received on Upsun platform' => [
                false,
                [],
                ['PLATFORM_RELATIONSHIPS' => true],
            ],
            'request via Fastly received on Upsun platform' => [
                true,
                ['Client-Cdn' => 'fastly'],
                ['PLATFORM_RELATIONSHIPS' => true],
            ],
        ];
    }

    /**
     * @dataProvider getTrustedHeaderEventSubscriberTestData
     */
    public function testTrustedHeaderEventSubscriberWithTrustedProxy(
        bool $isFromTrustedProxy,
        array $headers = [],
        array $server = []
    ): void {
        $request = $this->dispatchRequestEvent($headers, $server);

        self::assertEquals($isFromTrustedProxy, $request->isFromTrustedProxy());
        self::assertSame(self::TRUSTED_HEADER_SET, Request::getTrustedHeaderSet());
    }

    public function testTrustedHeaderEventSubscriberWithoutTrustedProxiesConfigured(): void
    {
        // framework.trusted_proxies not configured, so Symfony never sets a trusted header set
        Request::setTrustedProxies([], -1);

        $request = $this->dispatchRequestEvent(
            [
                'Client-Cdn' => 'fastly',
                'X-Forwarded-Host' => 'forged.example.com',
            ],
            ['PLATFORM_RELATIONSHIPS' => true]
        );

        self::assertFalse($request->isFromTrustedProxy());
        self::assertSame('localhost', $request->getHost());
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $server
     */
    private function dispatchRequestEvent(
        array $headers,
        array $server
    ): Request {
        $_SERVER['REMOTE_ADDR'] = self::REAL_CLIENT_IP;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(
            new TrustedHeaderClientIpEventSubscriber()
        );

        $request = Request::create('/', 'GET', [], [], [], array_merge(
            $server,
            ['REMOTE_ADDR' => self::REAL_CLIENT_IP],
        ));
        $request->headers->add($headers);

        $event = $eventDispatcher->dispatch(new RequestEvent(
            self::createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ), KernelEvents::REQUEST);

        return $event->getRequest();
    }
}
