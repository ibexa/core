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

class RejectExplicitFrontControllerRequestsListenerTest extends TestCase
{
    /** @var RejectExplicitFrontControllerRequestsListener */
    private $eventListener;

    /** @var HttpKernelInterface|MockObject */
    private $httpKernel;

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
            HttpKernelInterface::MAIN_REQUEST
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
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->eventListener->onKernelRequest($event);
    }

    public function validRequestDataProvider(): array
    {
        return [
            [
                Request::create(
                    'https://example.com',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/admin/dashboard',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/admin/dashboard',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/admin/dashboard/',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/admin/dashboard/',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/Folder/Content',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/Folder/Content',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/Folder/Content/',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/Folder/Content/',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/app.php-foo',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/app.php-foo',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/app.php.foo',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php.foo',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/folder/folder/app.php',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/folder/folder/app.php',
                        'SCRIPT_FILENAME' => 'index.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/app.php/folder/folder/',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/folder/',
                        'SCRIPT_FILENAME' => '',
                    ]
                ),
            ],
        ];
    }

    public function prohibitedRequestDataProvider(): array
    {
        return [
            [
                Request::create(
                    'https://example.com/app.php',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/app.php/app.php',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/app.php',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/folder/app.php',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/folder/app.php',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/app.php/foo',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/app.php/foo',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/app.php?foo=bar',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/app.php?foo=bar',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/app.php#foo',
                    Request::METHOD_GET,
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/app.php#foo',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
            [
                Request::create(
                    'https://example.com/folder/folder/app.php',
                    'GET',
                    [],
                    [],
                    [],
                    [
                        'REQUEST_URI' => 'https://example.com/app.php/folder/folder/app.php',
                        'SCRIPT_FILENAME' => 'app.php',
                    ]
                ),
            ],
        ];
    }
}
