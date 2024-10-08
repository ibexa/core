<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Converter;

use Ibexa\Bundle\Core\Converter\ContentParamConverter;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Symfony\Component\HttpFoundation\Request;

class ContentParamConverterTest extends AbstractParamConverterTest
{
    public const PROPERTY_NAME = 'contentId';

    public const CONTENT_CLASS = Content::class;

    /** @var \Ibexa\Bundle\Core\Converter\ContentParamConverter */
    private $converter;

    private $contentServiceMock;

    protected function setUp(): void
    {
        $this->contentServiceMock = $this->createMock(ContentService::class);
        $this->converter = new ContentParamConverter($this->contentServiceMock);
    }

    public function testSupports()
    {
        $config = $this->createConfiguration(self::CONTENT_CLASS);
        self::assertTrue($this->converter->supports($config));

        $config = $this->createConfiguration(__CLASS__);
        self::assertFalse($this->converter->supports($config));

        $config = $this->createConfiguration();
        self::assertFalse($this->converter->supports($config));
    }

    public function testApplyContent()
    {
        $id = 42;
        $valueObject = $this->createMock(Content::class);

        $this->contentServiceMock
            ->expects(self::once())
            ->method('loadContent')
            ->with($id)
            ->will(self::returnValue($valueObject));

        $request = new Request([], [], [self::PROPERTY_NAME => $id]);
        $config = $this->createConfiguration(self::CONTENT_CLASS, 'content');

        $this->converter->apply($request, $config);

        self::assertInstanceOf(self::CONTENT_CLASS, $request->attributes->get('content'));
    }

    public function testApplyContentOptionalWithEmptyAttribute()
    {
        $request = new Request([], [], [self::PROPERTY_NAME => null]);
        $config = $this->createConfiguration(self::CONTENT_CLASS, 'content');

        $config->expects(self::once())
            ->method('isOptional')
            ->will(self::returnValue(true));

        self::assertFalse($this->converter->apply($request, $config));
        self::assertNull($request->attributes->get('content'));
    }
}
