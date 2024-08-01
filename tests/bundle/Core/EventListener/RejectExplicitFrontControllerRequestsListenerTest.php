<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\RejectExplicitFrontControllerRequestsListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class RejectExplicitFrontControllerRequestsListenerTest extends TestCase
{
    private RejectExplicitFrontControllerRequestsListener $eventListener;

    private HttpKernelInterface & MockObject $httpKernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventListener = new RejectExplicitFrontControllerRequestsListener();
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
    }

    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [
                KernelEvents::REQUEST => [
                    ['onKernelRequest', 255],
                ],
            ],
            RejectExplicitFrontControllerRequestsListener::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider validRequestDataProvider
     *
     * @doesNotPerformAssertions
     */
    public function testOnKernelRequest(Request $request): void
    {
        $event = new RequestEvent(
            $this->httpKernel,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->eventListener->onKernelRequest($event);
    }

    /**
     * @dataProvider prohibitedRequestDataProvider
     */
    public function testOnKernelRequestThrowsException(Request $request): void
    {
        $this->expectException(NotFoundHttpException::class);

        $event = new RequestEvent(
            $this->httpKernel,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->eventListener->onKernelRequest($event);
    }

    /**
     * @return iterable<string, array{\Symfony\Component\HttpFoundation\Request}>
     */
    public function validRequestDataProvider(): iterable
    {
        yield 'site root' => [
            $this->buildRequest('https://example.com', 'https://example.com/app.php'),
        ];

        yield 'site root with slash' => [
            $this->buildRequest('https://example.com/', 'https://example.com/app.php/'),
        ];

        yield 'with path' => [
            $this->buildRequest('https://example.com/admin/dashboard', 'https://example.com/app.php/admin/dashboard'),
        ];

        yield 'with path with slash' => [
            $this->buildRequest('https://example.com/admin/dashboard/', 'https://example.com/app.php/admin/dashboard/'),
        ];

        yield 'with capital leading letter path' => [
            $this->buildRequest('https://example.com/Folder/Content', 'https://example.com/app.php/Folder/Content'),
        ];

        yield 'with capital leading letter path with slash' => [
            $this->buildRequest('https://example.com/Folder/Content/', 'https://example.com/app.php/Folder/Content/'),
        ];

        yield 'with php-foo extension' => [
            $this->buildRequest('https://example.com/app.php-foo', 'https://example.com/app.php/app.php-foo'),
        ];

        yield 'with php.foo extension' => [
            $this->buildRequest('https://example.com/app.php.foo', 'https://example.com/app.php.foo'),
        ];

        yield 'with php extension' => [
            $this->buildRequest(
                'https://example.com/folder/folder/app.php',
                'https://example.com/app.php/folder/folder/app.php'
            ),
        ];
    }

    /**
     * @return iterable<string, array{\Symfony\Component\HttpFoundation\Request}>
     */
    public function prohibitedRequestDataProvider(): iterable
    {
        yield 'with explicit front controller' => [
            $this->buildRequest('https://example.com/app.php', 'https://example.com/app.php'),
        ];

        yield 'with front controller in path' => [
            $this->buildRequest('https://example.com/app.php/app.php', 'https://example.com/app.php/app.php'),
        ];

        yield 'with an arbitrary path' => [
            $this->buildRequest('https://example.com/folder/app.php', 'https://example.com/app.php/folder/app.php'),
        ];

        yield 'with path after front controller' => [
            $this->buildRequest('https://example.com/app.php/foo', 'https://example.com/app.php/app.php/foo'),
        ];

        yield 'with query parameter' => [
            $this->buildRequest('https://example.com/app.php?foo=bar', 'https://example.com/app.php/app.php?foo=bar'),
        ];

        yield 'with fragment' => [
            $this->buildRequest('https://example.com/app.php#foo', 'https://example.com/app.php/app.php#foo'),
        ];
    }

    private function buildRequest(string $uri, string $requestUri): Request
    {
        return Request::create(
            $uri,
            Request::METHOD_GET,
            [],
            [],
            [],
            [
                'REQUEST_URI' => $requestUri,
                'SCRIPT_FILENAME' => 'app.php',
            ]
        );
    }
}
