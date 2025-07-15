<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\EventListener;

use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Core\IO\IOConfigProvider;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens for IO files requests, and streams them.
 *
 * @internal
 */
class StreamFileListener implements EventSubscriberInterface
{
    private IOServiceInterface $ioService;

    private IOConfigProvider $ioConfigResolver;

    public function __construct(IOServiceInterface $ioService, IOConfigProvider $ioConfigResolver)
    {
        $this->ioService = $ioService;
        $this->ioConfigResolver = $ioConfigResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 42],
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
            return;
        }

        $request = $event->getRequest();
        $urlPrefix = $this->ioConfigResolver->getUrlPrefix();
        $pathInfo = $request->getPathInfo();

        if (str_contains($urlPrefix, '://')) {
            $uri = $request->getSchemeAndHttpHost() . $pathInfo;
        } else {
            $uri = $pathInfo;
        }

        if (!$this->isIoUri($uri, $urlPrefix)) {
            return;
        }

        $binaryFile = $this->ioService->loadBinaryFileByUri($uri);
        if ($binaryFile instanceof MissingBinaryFile) {
            throw new NotFoundHttpException("Could not find 'BinaryFile' with identifier '$uri'");
        }

        $event->setResponse(
            new BinaryStreamResponse(
                $binaryFile,
                $this->ioService
            )
        );
    }

    /**
     * Tests if $uri is an IO file uri root.
     */
    private function isIoUri(string $uri, string $urlPrefix): bool
    {
        return str_starts_with(ltrim($uri, '/'), $urlPrefix);
    }
}
