<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\IndexRequestListener;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class IndexRequestListenerTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private MockObject $configResolver;

    /** @var \Ibexa\Bundle\Core\EventListener\IndexRequestListener */
    private IndexRequestListener $indexRequestEventListener;

    /** @var \Symfony\Component\HttpFoundation\Request */
    private MockObject $request;

    /** @var \Symfony\Component\HttpKernel\Event\RequestEvent */
    private RequestEvent $event;

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    private MockObject $httpKernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configResolver = $this->createMock(ConfigResolverInterface::class);

        $this->indexRequestEventListener = new IndexRequestListener($this->configResolver);

        $this->request = $this
            ->getMockBuilder(Request::class)
            ->setMethods(['getSession', 'hasSession'])
            ->getMock();

        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->event = new RequestEvent(
            $this->httpKernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [
                KernelEvents::REQUEST => [
                    ['onKernelRequestIndex', 40],
                ],
            ],
            $this->indexRequestEventListener->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider indexPageProvider
     */
    public function testOnKernelRequestIndexOnIndexPage(string $requestPath, string $configuredIndexPath, string $expectedIndexPath): void
    {
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('index_page')
            ->will(self::returnValue($configuredIndexPath));
        $this->request->attributes->set('semanticPathinfo', $requestPath);
        $this->indexRequestEventListener->onKernelRequestIndex($this->event);
        self::assertEquals($expectedIndexPath, $this->request->attributes->get('semanticPathinfo'));
        self::assertTrue($this->request->attributes->get('needsRedirect'));
    }

    public function indexPageProvider(): array
    {
        return [
            ['/', '/foo', '/foo'],
            ['/', '/foo/', '/foo/'],
            ['/', '/foo/bar', '/foo/bar'],
            ['/', 'foo/bar', '/foo/bar'],
            ['', 'foo/bar', '/foo/bar'],
            ['', '/foo/bar', '/foo/bar'],
            ['', '/foo/bar/', '/foo/bar/'],
        ];
    }

    public function testOnKernelRequestIndexNotOnIndexPage(): void
    {
        $this->request->attributes->set('semanticPathinfo', '/anyContent');
        $this->indexRequestEventListener->onKernelRequestIndex($this->event);
        self::assertFalse($this->request->attributes->has('needsRedirect'));
    }
}
