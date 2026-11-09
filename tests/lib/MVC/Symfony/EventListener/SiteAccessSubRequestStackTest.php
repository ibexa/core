<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\EventListener;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\EventListener\SiteAccessMatchListener;
use Ibexa\Core\MVC\Symfony\EventListener\SiteAccessRestoreListener;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Covers the push/pop pairing between SiteAccessMatchListener and SiteAccessRestoreListener
 * on a real SiteAccessService, for a sub-request nested in an already changed SiteAccess
 * (e.g. content preview).
 */
#[CoversClass(SiteAccessMatchListener::class)]
#[CoversClass(SiteAccessRestoreListener::class)]
final class SiteAccessSubRequestStackTest extends TestCase
{
    private EventDispatcher $eventDispatcher;

    private RequestStack $requestStack;

    private SiteAccessService $siteAccessService;

    private SiteAccessMatchListener $matchListener;

    private SiteAccessRestoreListener $restoreListener;

    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->requestStack = new RequestStack();
        $this->kernel = self::createStub(HttpKernelInterface::class);
        $this->siteAccessService = new SiteAccessService(
            self::createStub(SiteAccessProviderInterface::class),
            self::createStub(ConfigResolverInterface::class),
            $this->eventDispatcher
        );
        $this->matchListener = new SiteAccessMatchListener(
            self::createStub(Router::class),
            $this->eventDispatcher,
            self::createStub(SerializerInterface::class),
            $this->siteAccessService
        );
        $this->restoreListener = new SiteAccessRestoreListener(
            $this->requestStack,
            $this->eventDispatcher,
            $this->siteAccessService
        );

        $this->eventDispatcher->addSubscriber($this->siteAccessService);
    }

    public function testSubRequestSiteAccessIsPoppedWhenSubRequestFinishes(): void
    {
        $previewSiteAccess = $this->startPreviewInMainRequest();
        $subRequestSiteAccess = new SiteAccess('fragment_sa', 'uri:element');
        $subRequest = $this->createRequest($subRequestSiteAccess);

        $this->handleSubRequest($subRequest);
        self::assertSame($subRequestSiteAccess, $this->siteAccessService->getCurrent());

        $this->finishSubRequest($subRequest);
        self::assertSame($previewSiteAccess, $this->siteAccessService->getCurrent());
    }

    public function testParentSiteAccessSurvivesSiteAccessListenerFailingInSubRequest(): void
    {
        $previewSiteAccess = $this->startPreviewInMainRequest();
        $subRequest = $this->createRequest(new SiteAccess('fragment_sa', 'uri:element'));

        $this->eventDispatcher->addListener(
            MVCEvents::SITEACCESS,
            static function (PostSiteAccessMatchEvent $event) use ($subRequest): void {
                if ($event->getRequest() === $subRequest) {
                    throw new RuntimeException('MVCEvents::SITEACCESS listener failed');
                }
            }
        );

        try {
            $this->handleSubRequest($subRequest);
            self::fail('The MVCEvents::SITEACCESS listener exception should propagate');
        } catch (RuntimeException) {
            // HttpKernel still dispatches kernel.finish_request for a failed sub-request
        }

        $this->finishSubRequest($subRequest);
        self::assertSame($previewSiteAccess, $this->siteAccessService->getCurrent());
    }

    private function startPreviewInMainRequest(): SiteAccess
    {
        $mainRequest = $this->createRequest(new SiteAccess('admin', 'uri:element'));
        $this->requestStack->push($mainRequest);
        $this->matchListener->onKernelRequest(
            new RequestEvent($this->kernel, $mainRequest, HttpKernelInterface::MAIN_REQUEST)
        );

        $previewSiteAccess = new SiteAccess('site', 'uri:element');
        $this->siteAccessService->changeSiteAccess($previewSiteAccess);

        return $previewSiteAccess;
    }

    private function handleSubRequest(Request $subRequest): void
    {
        $this->requestStack->push($subRequest);
        $this->matchListener->onKernelRequest(
            new RequestEvent($this->kernel, $subRequest, HttpKernelInterface::SUB_REQUEST)
        );
    }

    private function finishSubRequest(Request $subRequest): void
    {
        $this->restoreListener->onKernelFinishRequest(
            new FinishRequestEvent($this->kernel, $subRequest, HttpKernelInterface::SUB_REQUEST)
        );
        $this->requestStack->pop();
    }

    private function createRequest(SiteAccess $siteAccess): Request
    {
        $request = Request::create('/');
        $request->attributes->set('siteaccess', $siteAccess);

        return $request;
    }
}
