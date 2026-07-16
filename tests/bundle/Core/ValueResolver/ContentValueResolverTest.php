<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\ValueResolver;

use Ibexa\Bundle\Core\ValueResolver\ContentValueResolver;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class ContentValueResolverTest extends TestCase
{
    private ContentValueResolver $resolver;

    private MockObject & ContentService $contentServiceMock;

    protected function setUp(): void
    {
        $this->contentServiceMock = $this->createMock(ContentService::class);
        $this->resolver = new ContentValueResolver($this->contentServiceMock);
    }

    public function testResolveWithValidContentId(): void
    {
        $request = new Request([], [], ['contentId' => '123']);
        $argumentMetadata = $this->createMock(ArgumentMetadata::class);
        $argumentMetadata->method('getType')->willReturn(Content::class);

        $mockContent = $this->createMock(Content::class);

        $this->contentServiceMock
            ->expects(self::once())
            ->method('loadContent')
            ->with(123)
            ->willReturn($mockContent);

        $result = iterator_to_array($this->resolver->resolve($request, $argumentMetadata));

        self::assertSame([$mockContent], $result);
    }

    public function testResolveWithInvalidContentId(): void
    {
        $request = new Request([], [], ['contentId' => 'invalid']);
        $argumentMetadata = $this->createMock(ArgumentMetadata::class);
        $argumentMetadata->method('getType')->willReturn(Content::class);

        $this->contentServiceMock
            ->expects(self::never())
            ->method('loadContent');

        $result = iterator_to_array($this->resolver->resolve($request, $argumentMetadata));

        self::assertSame([], $result);
    }

    public function testResolveWithNonContentType(): void
    {
        $request = new Request([], [], ['contentId' => '123']);
        $argumentMetadata = $this->createMock(ArgumentMetadata::class);
        $argumentMetadata->method('getType')->willReturn('OtherClass');

        $this->contentServiceMock
            ->expects(self::never())
            ->method('loadContent');

        $result = iterator_to_array($this->resolver->resolve($request, $argumentMetadata));

        self::assertSame([], $result);
    }
}
