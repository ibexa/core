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
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AliasGeneratorTest extends TestCase
{
    /** @var MockObject|LoaderInterface */
    private $dataLoader;

    /** @var MockObject|FilterManager */
    private $filterManager;

    /** @var MockObject|ResolverInterface */
    private $ioResolver;

    /** @var FilterConfiguration */
    private $filterConfiguration;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|ImagineInterface */
    private $imagine;

    /** @var AliasGenerator */
    private $aliasGenerator;

    /** @var VariationHandler */
    private $decoratedAliasGenerator;

    /** @var MockObject|BoxInterface */
    private $box;

    /** @var MockObject|ImageInterface */
    private $image;

    /** @var MockObject|IOServiceInterface */
    private $ioService;

    /** @var MockObject|VariationPathGenerator */
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
     * @param FieldTypeValue $value
     * @param bool $isSupported
     */
    public function testSupportsValue(
        $value,
        $isSupported
    ) {
        $this->assertSame($isSupported, $this->aliasGenerator->supportsValue($value));
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
            [$this->createStub(FieldTypeValue::class), false],
            [new TextLineValue(), false],
            [new ImageValue(), true],
            [$this->createStub(ImageValue::class), true],
        ];
    }

    public function testGetVariationWrongValue()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = new Field(['value' => $this->createStub(FieldTypeValue::class)]);
        $this->aliasGenerator->getVariation($field, new VersionInfo(), 'foo');
    }

    /**
     * Test obtaining Image Variation that hasn't been stored yet.
     *
     * @throws InvalidArgumentType
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
            ->expects($this->once())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will($this->returnValue(false));

        $this->logger
            ->expects($this->once())
            ->method('debug');

        $binary = $this->createStub(BinaryInterface::class);
        $this->dataLoader
            ->expects($this->once())
            ->method('find')
            ->with($originalPath)
            ->will($this->returnValue($binary));
        $this->filterManager
            ->expects($this->once())
            ->method('applyFilter')
            ->with($binary, $variationName)
            ->will($this->returnValue($binary));
        $this->ioResolver
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will($this->returnValue(false));

        $this->logger
            ->expects($this->once())
            ->method('debug');

        $this->ioResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($originalPath, $variationName)
            ->will($this->returnValue($expectedUrl));

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
        $this->assertEquals($expected, $this->decoratedAliasGenerator->getVariation($field, new VersionInfo(), $variationName));
    }

    /**
     * Test obtaining Image Variation that hasn't been stored yet and has multiple references.
     *
     * @throws InvalidArgumentType
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
            ->expects($this->once())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will($this->returnValue(false));

        $this->logger
            ->expects($this->once())
            ->method('debug');

        $binary = $this->createStub(BinaryInterface::class);
        $this->dataLoader
            ->expects($this->once())
            ->method('find')
            ->with($originalPath)
            ->will($this->returnValue($binary));

        // Filter manager is supposed to be called 3 times to generate references, and then passed variation.
        $this->filterManager
            ->expects($this->exactly(3))
            ->method('applyFilter')
            ->withConsecutive(
                [$binary, $reference2],
                [$binary, $reference1],
                [$binary, $variationName]
            )
            ->willReturn($binary);

        $this->ioResolver
            ->expects($this->once())
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
     * @throws InvalidArgumentType
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
            ->expects($this->once())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will($this->returnValue(true));

        $this->logger
            ->expects($this->once())
            ->method('debug');

        $this->dataLoader
            ->expects($this->never())
            ->method('find');
        $this->filterManager
            ->expects($this->never())
            ->method('applyFilter');
        $this->ioResolver
            ->expects($this->never())
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
            ->expects($this->once())
            ->method('find')
            ->will($this->throwException(new NotLoadableException()));

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
            ->expects($this->once())
            ->method('isStored')
            ->with($originalPath, $variationName)
            ->will($this->returnValue(true));

        $this->logger
            ->expects($this->once())
            ->method('debug');

        $this->dataLoader
            ->expects($this->never())
            ->method('find');
        $this->filterManager
            ->expects($this->never())
            ->method('applyFilter');
        $this->ioResolver
            ->expects($this->never())
            ->method('store');

        $this->ioResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($originalPath, $variationName)
            ->will($this->throwException(new NotResolvableException()));

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
     * @throws InvalidArgumentType
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
            ->expects($this->once())
            ->method('resolve')
            ->with($originalPath, $variationName)
            ->will($this->returnValue($expectedUrl));

        $this->variationPathGenerator
            ->expects($this->once())
            ->method('getVariationPath')
            ->with($originalPath, $variationName)
            ->willReturn($binaryFile->uri);

        $this->ioService
            ->expects($this->once())
            ->method('loadBinaryFile')
            ->withAnyParameters()
            ->willReturn($binaryFile);

        $this->ioService
            ->expects($this->once())
            ->method('getFileContents')
            ->with($binaryFile)
            ->willReturn('file contents mock');

        $this->imagine
            ->expects($this->once())
            ->method('load')
            ->with('file contents mock')
            ->will($this->returnValue($this->image));
        $this->image
            ->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue($this->box));

        $this->box
            ->expects($this->once())
            ->method('getWidth')
            ->will($this->returnValue($imageWidth));
        $this->box
            ->expects($this->once())
            ->method('getHeight')
            ->will($this->returnValue($imageHeight));

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
        $this->assertEquals(
            $expected,
            $this->decoratedAliasGenerator->getVariation($field, new VersionInfo(), $variationName)
        );
    }
}

class_alias(AliasGeneratorTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\Imagine\AliasGeneratorTest');
