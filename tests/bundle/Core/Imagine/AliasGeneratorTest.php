<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine;

use Ibexa\Bundle\Core\Imagine\AliasGenerator;
use Ibexa\Bundle\Core\Imagine\Variation\ImagineAwareAliasGenerator;
use Ibexa\Contracts\Core\FieldType\Value as FieldTypeValue;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidVariationException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Variation\Values\ImageVariation;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\MVC\Exception\SourceImageNotFoundException;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Exception\Imagine\Cache\Resolver\NotResolvableException;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AliasGeneratorTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Liip\ImagineBundle\Binary\Loader\LoaderInterface */
    private $dataLoader;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Liip\ImagineBundle\Imagine\Filter\FilterManager */
    private $filterManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface */
    private $ioResolver;

    /** @var \Liip\ImagineBundle\Imagine\Filter\FilterConfiguration */
    private $filterConfiguration;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Log\LoggerInterface */
    private $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Imagine\Image\ImagineInterface */
    private $imagine;

    /** @var \Ibexa\Bundle\Core\Imagine\AliasGenerator */
    private $aliasGenerator;

    /** @var \Ibexa\Contracts\Core\Variation\VariationHandler */
    private $decoratedAliasGenerator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Imagine\Image\BoxInterface */
    private $box;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Imagine\Image\ImageInterface */
    private $image;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\IO\IOServiceInterface */
    private $ioService;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\Variation\VariationPathGenerator */
    private $variationPathGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataLoader = $this->createMock(LoaderInterface::class);
        $this->filterManager = $this
            ->getMockBuilder(FilterManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ioResolver = $this->createMock(ResolverInterface::class);
        $this->filterConfiguration = new FilterConfiguration();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->imagine = $this->createMock(ImagineInterface::class);
        $this->box = $this->createMock(BoxInterface::class);
        $this->image = $this->createMock(ImageInterface::class);
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->variationPathGenerator = $this->createMock(VariationPathGenerator::class);
        $this->aliasGenerator = new AliasGenerator(
            $this->dataLoader,
            $this->filterManager,
            $this->ioResolver,
            $this->filterConfiguration,
            $this->logger
        );
        $this->decoratedAliasGenerator = new ImagineAwareAliasGenerator(
            $this->aliasGenerator,
            $this->variationPathGenerator,
            $this->ioService,
            $this->imagine
        );
    }

    /**
     * @dataProvider supportsValueProvider
     *
     * @param \Ibexa\Contracts\Core\FieldType\Value $value
     * @param bool $isSupported
     */
    public function testSupportsValue($value, $isSupported)
    {
        self::assertSame($isSupported, $this->aliasGenerator->supportsValue($value));
    }

    /**
     * Data provider for testSupportsValue.
     *
     * @see testSupportsValue
     *
     * @return array
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function supportsValueProvider()
    {
        return [
            [$this->createMock(FieldTypeValue::class), false],
            [new TextLineValue(), false],
            [new ImageValue(), true],
            [$this->createMock(ImageValue::class), true],
        ];
    }

    public function testGetVariationWrongValue()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = new Field(['value' => $this->createMock(FieldTypeValue::class)]);
        $this->aliasGenerator->getVariation($field, new VersionInfo(), 'foo');
    }

    /**
     * Test obtaining Image Variation that hasn't been stored yet.
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     */
    public function testGetVariationNotStored()
    {
        $originalPath = 'foo/bar/image.jpg';
        $variationName = 'my_variation';
        $this->filterConfiguration->set($variationName, []);
        $imageId = '123-45';
        $imageWidth = 300;
        $imageHeight = 300;
        $expectedUrl = "http://localhost/foo/bar/image_$variationName.jpg";

        $this->ioResolver
            ->expects(self::once())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will(self::returnValue(false));

        $this->logger
            ->expects(self::once())
            ->method('debug');

        $binary = $this->createMock(BinaryInterface::class);
        $this->dataLoader
            ->expects(self::once())
            ->method('find')
            ->with($originalPath)
            ->will(self::returnValue($binary));
        $this->filterManager
            ->expects(self::once())
            ->method('applyFilter')
            ->with($binary, $variationName)
            ->will(self::returnValue($binary));
        $this->ioResolver
            ->expects(self::once())
            ->method('store')
            ->with($binary, $originalPath, $variationName);

        $this->assertImageVariationIsCorrect(
            $expectedUrl,
            $variationName,
            $imageId,
            $originalPath,
            $imageWidth,
            $imageHeight
        );
    }

    public function testGetVariationOriginal()
    {
        $originalPath = 'foo/bar/image.jpg';
        $variationName = 'original';
        $imageId = '123-45';
        $imageWidth = 300;
        $imageHeight = 300;
        // original images already contain proper width and height
        $imageValue = new ImageValue(
            [
                'id' => $originalPath,
                'imageId' => $imageId,
                'width' => $imageWidth,
                'height' => $imageHeight,
            ]
        );
        $field = new Field(['value' => $imageValue]);
        $expectedUrl = 'http://localhost/foo/bar/image.jpg';

        $this->ioResolver
            ->expects(self::never())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will(self::returnValue(false));

        $this->logger
            ->expects(self::once())
            ->method('debug');

        $this->ioResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($originalPath, $variationName)
            ->will(self::returnValue($expectedUrl));

        $expected = new ImageVariation(
            [
                'name' => $variationName,
                'fileName' => 'image.jpg',
                'dirPath' => 'http://localhost/foo/bar',
                'uri' => $expectedUrl,
                'imageId' => $imageId,
                'height' => $imageHeight,
                'width' => $imageWidth,
            ]
        );
        self::assertEquals($expected, $this->decoratedAliasGenerator->getVariation($field, new VersionInfo(), $variationName));
    }

    /**
     * Test obtaining Image Variation that hasn't been stored yet and has multiple references.
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     */
    public function testGetVariationNotStoredHavingReferences()
    {
        $originalPath = 'foo/bar/image.jpg';
        $variationName = 'my_variation';
        $reference1 = 'reference1';
        $reference2 = 'reference2';
        $configVariation = ['reference' => $reference1];
        $configReference1 = ['reference' => $reference2];
        $configReference2 = [];
        $this->filterConfiguration->set($variationName, $configVariation);
        $this->filterConfiguration->set($reference1, $configReference1);
        $this->filterConfiguration->set($reference2, $configReference2);
        $imageId = '123-45';
        $imageWidth = 300;
        $imageHeight = 300;
        $expectedUrl = "http://localhost/foo/bar/image_$variationName.jpg";

        $this->ioResolver
            ->expects(self::once())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will(self::returnValue(false));

        $this->logger
            ->expects(self::once())
            ->method('debug');

        $binary = $this->createMock(BinaryInterface::class);
        $this->dataLoader
            ->expects(self::once())
            ->method('find')
            ->with($originalPath)
            ->will(self::returnValue($binary));

        // Filter manager is supposed to be called 3 times to generate references, and then passed variation.
        $this->filterManager
            ->expects(self::at(0))
            ->method('applyFilter')
            ->with($binary, $reference2)
            ->will(self::returnValue($binary));
        $this->filterManager
            ->expects(self::at(1))
            ->method('applyFilter')
            ->with($binary, $reference1)
            ->will(self::returnValue($binary));
        $this->filterManager
            ->expects(self::at(2))
            ->method('applyFilter')
            ->with($binary, $variationName)
            ->will(self::returnValue($binary));

        $this->ioResolver
            ->expects(self::once())
            ->method('store')
            ->with($binary, $originalPath, $variationName);

        $this->assertImageVariationIsCorrect(
            $expectedUrl,
            $variationName,
            $imageId,
            $originalPath,
            $imageWidth,
            $imageHeight
        );
    }

    /**
     * Test obtaining Image Variation that has been stored already.
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     */
    public function testGetVariationAlreadyStored()
    {
        $originalPath = 'foo/bar/image.jpg';
        $variationName = 'my_variation';
        $imageId = '123-45';
        $imageWidth = 300;
        $imageHeight = 300;
        $expectedUrl = "http://localhost/foo/bar/image_$variationName.jpg";

        $this->ioResolver
            ->expects(self::once())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will(self::returnValue(true));

        $this->logger
            ->expects(self::once())
            ->method('debug');

        $this->dataLoader
            ->expects(self::never())
            ->method('find');
        $this->filterManager
            ->expects(self::never())
            ->method('applyFilter');
        $this->ioResolver
            ->expects(self::never())
            ->method('store');

        $this->assertImageVariationIsCorrect(
            $expectedUrl,
            $variationName,
            $imageId,
            $originalPath,
            $imageWidth,
            $imageHeight
        );
    }

    public function testGetVariationOriginalNotFound()
    {
        $this->expectException(SourceImageNotFoundException::class);

        $this->dataLoader
            ->expects(self::once())
            ->method('find')
            ->will(self::throwException(new NotLoadableException()));

        $field = new Field(['value' => new ImageValue()]);
        $this->aliasGenerator->getVariation($field, new VersionInfo(), 'foo');
    }

    public function testGetVariationInvalidVariation()
    {
        $this->expectException(InvalidVariationException::class);

        $originalPath = 'foo/bar/image.jpg';
        $variationName = 'my_variation';
        $imageId = '123-45';
        $imageValue = new ImageValue(['id' => $originalPath, 'imageId' => $imageId]);
        $field = new Field(['value' => $imageValue]);

        $this->ioResolver
            ->expects(self::once())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will(self::returnValue(true));

        $this->logger
            ->expects(self::once())
            ->method('debug');

        $this->dataLoader
            ->expects(self::never())
            ->method('find');
        $this->filterManager
            ->expects(self::never())
            ->method('applyFilter');
        $this->ioResolver
            ->expects(self::never())
            ->method('store');

        $this->ioResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($originalPath, $variationName)
            ->will(self::throwException(new NotResolvableException()));

        $this->aliasGenerator->getVariation($field, new VersionInfo(), $variationName);
    }

    /**
     * Prepare required Imagine-related mocks and assert that the Image Variation is as expected.
     *
     * @param string $expectedUrl
     * @param string $variationName
     * @param string $imageId
     * @param string $originalPath
     * @param int $imageWidth
     * @param int $imageHeight
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     */
    protected function assertImageVariationIsCorrect(
        $expectedUrl,
        $variationName,
        $imageId,
        $originalPath,
        $imageWidth,
        $imageHeight
    ) {
        $imageValue = new ImageValue(['id' => $originalPath, 'imageId' => $imageId]);
        $field = new Field(['value' => $imageValue]);

        $binaryFile = new BinaryFile(
            [
                'uri' => "_aliases/{$variationName}/foo/bar/image.jpg",
            ]
        );

        $this->ioResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($originalPath, $variationName)
            ->will(self::returnValue($expectedUrl));

        $this->variationPathGenerator
            ->expects(self::once())
            ->method('getVariationPath')
            ->with($originalPath, $variationName)
            ->willReturn($binaryFile->uri);

        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->withAnyParameters()
            ->willReturn($binaryFile);

        $this->ioService
            ->expects(self::once())
            ->method('getFileContents')
            ->with($binaryFile)
            ->willReturn('file contents mock');

        $this->imagine
            ->expects(self::once())
            ->method('load')
            ->with('file contents mock')
            ->will(self::returnValue($this->image));
        $this->image
            ->expects(self::once())
            ->method('getSize')
            ->will(self::returnValue($this->box));

        $this->box
            ->expects(self::once())
            ->method('getWidth')
            ->will(self::returnValue($imageWidth));
        $this->box
            ->expects(self::once())
            ->method('getHeight')
            ->will(self::returnValue($imageHeight));

        $expected = new ImageVariation(
            [
                'name' => $variationName,
                'fileName' => "image_$variationName.jpg",
                'dirPath' => 'http://localhost/foo/bar',
                'uri' => $expectedUrl,
                'imageId' => $imageId,
                'height' => $imageHeight,
                'width' => $imageWidth,
            ]
        );
        self::assertEquals(
            $expected,
            $this->decoratedAliasGenerator->getVariation($field, new VersionInfo(), $variationName)
        );
    }
}
