<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\ValueResolver;

use Ibexa\Bundle\Core\ValueResolver\LocationValueResolver;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Helper\ContentPreviewHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class LocationValueResolverTest extends TestCase
{
    private LocationValueResolver $resolver;

    private MockObject & LocationService $locationServiceMock;

    private MockObject & ContentPreviewHelper $contentPreviewHelperMock;

    protected function setUp(): void
    {
        $this->locationServiceMock = $this->createMock(LocationService::class);
        $this->contentPreviewHelperMock = $this->createMock(ContentPreviewHelper::class);
        $this->resolver = new LocationValueResolver(
            $this->locationServiceMock,
            $this->contentPreviewHelperMock
        );
    }

    public function testResolveWithValidLocationId(): void
    {
        $request = new Request([], [], ['locationId' => '456']);
        $argumentMetadata = $this->createMock(ArgumentMetadata::class);
        $argumentMetadata->method('getType')->willReturn(Location::class);

        $mockLocation = $this->createMock(Location::class);

        $this->contentPreviewHelperMock
            ->method('isPreviewActive')
            ->willReturn(true);

        $this->locationServiceMock
            ->expects(self::once())
            ->method('loadLocation')
            ->with(456, Language::ALL)
            ->willReturn($mockLocation);

        $result = iterator_to_array($this->resolver->resolve($request, $argumentMetadata));

        self::assertSame([$mockLocation], $result);
    }

    public function testResolveWithInvalidLocationId(): void
    {
        $request = new Request([], [], ['locationId' => 'invalid']);
        $argumentMetadata = $this->createMock(ArgumentMetadata::class);
        $argumentMetadata->method('getType')->willReturn(Location::class);

        $this->locationServiceMock
            ->expects(self::never())
            ->method('loadLocation');

        $result = iterator_to_array($this->resolver->resolve($request, $argumentMetadata));

        self::assertSame([], $result);
    }

    public function testResolveWithNonLocationType(): void
    {
        $request = new Request([], [], ['locationId' => '456']);
        $argumentMetadata = $this->createMock(ArgumentMetadata::class);
        $argumentMetadata->method('getType')->willReturn('OtherClass');

        $this->locationServiceMock
            ->expects(self::never())
            ->method('loadLocation');

        $result = iterator_to_array($this->resolver->resolve($request, $argumentMetadata));

        self::assertSame([], $result);
    }
}
