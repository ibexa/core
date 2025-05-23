<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentAwareInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Core\MVC\Symfony\Routing\Generator\RouteReferenceGenerator;
use Ibexa\Core\MVC\Symfony\Routing\Generator\RouteReferenceGeneratorInterface;
use Ibexa\Core\MVC\Symfony\Routing\RouteReference;
use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\RoutingExtension;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Test\IntegrationTestCase;

final class RoutingExtensionTest extends IntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [
            new RoutingExtension(
                $this->getRouteReferenceGenerator(),
                $this->getUrlGenerator()
            ),
        ];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/_fixtures/routing_functions';
    }

    protected function getExampleContent(int $id): APIContent
    {
        return new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => $this->getExampleContentInfo($id),
            ]),
        ]);
    }

    protected function getExampleContentAware(int $id): ContentAwareInterface
    {
        $contentAware = $this->createMock(ContentAwareInterface::class);
        $contentAware->method('getContent')->willReturn($this->getExampleContent($id));

        return $contentAware;
    }

    protected function getExampleContentInfo(int $id): ContentInfo
    {
        return new ContentInfo([
            'id' => $id,
        ]);
    }

    protected function getExampleLocation(int $id): APILocation
    {
        return new Location(['id' => $id]);
    }

    protected function getExampleRouteReference($name, array $parameters = []): RouteReference
    {
        return new RouteReference($name, $parameters);
    }

    protected function getExampleUnsupportedObject(): object
    {
        $object = new stdClass();
        $object->foo = 'foo';
        $object->bar = 'bar';

        return $object;
    }

    private function getRouteReferenceGenerator(): RouteReferenceGeneratorInterface
    {
        $generator = new RouteReferenceGenerator(
            $this->createMock(EventDispatcherInterface::class)
        );
        $request = new Request();
        $requestStack = new RequestStack([$request]);
        $generator->setRequestStack($requestStack);

        return $generator;
    }

    private function getUrlGenerator(): UrlGeneratorInterface
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator
            ->method('generate')
            ->willReturnCallback(static function ($name, $parameters, $referenceType): string {
                return json_encode([
                    '$name' => $name,
                    '$parameters' => $parameters,
                    '$referenceType' => $referenceType,
                ]);
            });

        return $generator;
    }
}
