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

        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $event->getRequest();

        self::assertEquals($isFromTrustedProxy, $request->isFromTrustedProxy());
    }
}
