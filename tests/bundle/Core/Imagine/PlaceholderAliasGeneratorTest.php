<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine;

use Ibexa\Bundle\Core\Imagine\IORepositoryResolver;
use Ibexa\Bundle\Core\Imagine\PlaceholderAliasGenerator;
use Ibexa\Bundle\Core\Imagine\PlaceholderProvider;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Variation\Values\ImageVariation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\FieldType\Null\Value as NullValue;
use Ibexa\Core\FieldType\Value;
use Ibexa\Core\FieldType\Value as FieldTypeValue;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Liip\ImagineBundle\Exception\Imagine\Cache\Resolver\NotResolvableException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceholderAliasGeneratorTest extends TestCase
{
    /** @var PlaceholderAliasGenerator */
    private $aliasGenerator;

    /** @var VariationHandler|MockObject */
    private $innerAliasGenerator;

    /** @var IOServiceInterface|MockObject */
    private $ioService;

    /** @var IORepositoryResolver|MockObject */
    private $ioResolver;

    /** @var PlaceholderProvider|MockObject */
    private $placeholderProvider;

    /** @var array */
    private $placeholderOptions;

    protected function setUp(): void
    {
        $this->innerAliasGenerator = $this->createMock(VariationHandler::class);
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->ioResolver = $this->createMock(IORepositoryResolver::class);
        $this->placeholderProvider = $this->createMock(PlaceholderProvider::class);
        $this->placeholderOptions = [
            'foo' => 'foo',
            'bar' => 'bar',
        ];

        $this->aliasGenerator = new PlaceholderAliasGenerator(
            $this->innerAliasGenerator,
            $this->ioResolver,
            $this->ioService
        );
    }

    public function testGetVariationWrongValue()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = new Field([
            'value' => $this->createMock(FieldTypeValue::class),
            'fieldDefIdentifier' => 'image',
        ]);

        $this->aliasGenerator->setPlaceholderProvider(
            $this->placeholderProvider,
            $this->placeholderOptions
        );
        $this->aliasGenerator->getVariation($field, new VersionInfo(), 'foo');
    }

    /**
     * @dataProvider getVariationProvider
     */
    public function testGetVariationSkipsPlaceholderGeneration(
        Field $field,
        APIVersionInfo $versionInfo,
        string $variationName,
        array $parameters
    ) {
        $expectedVariation = $this->createMock(ImageVariation::class);

        $this->ioResolver
            ->expects(self::never())
            ->method('resolve')
            ->with($field->value->id, IORepositoryResolver::VARIATION_ORIGINAL);

        $this->placeholderProvider
            ->expects(self::never())
            ->method('getPlaceholder')
            ->with($field->value, $this->placeholderOptions);

        $this->innerAliasGenerator
            ->expects(self::once())
            ->method('getVariation')
            ->with($field, $versionInfo, $variationName, $parameters)
            ->willReturn($expectedVariation);

        $actualVariation = $this->aliasGenerator->getVariation(
            $field,
            $versionInfo,
            $variationName,
            $parameters
        );

        self::assertEquals($expectedVariation, $actualVariation);
    }

    /**
     * @dataProvider getVariationProvider
     */
    public function testGetVariationOriginalFound(
        Field $field,
        APIVersionInfo $versionInfo,
        string $variationName,
        array $parameters
    ) {
        $expectedVariation = $this->createMock(ImageVariation::class);

        $this->ioResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($field->value->id, IORepositoryResolver::VARIATION_ORIGINAL);

        $this->innerAliasGenerator
            ->expects(self::once())
            ->method('getVariation')
            ->with($field, $versionInfo, $variationName, $parameters)
            ->willReturn($expectedVariation);

        $this->aliasGenerator->setPlaceholderProvider(
            $this->placeholderProvider,
            $this->placeholderOptions
        );

        $actualVariation = $this->aliasGenerator->getVariation(
            $field,
            $versionInfo,
            $variationName,
            $parameters
        );

        self::assertEquals($expectedVariation, $actualVariation);
    }

    /**
     * @dataProvider getVariationProvider
     */
    public function testGetVariationOriginalNotFound(
        Field $field,
        APIVersionInfo $versionInfo,
        string $variationName,
        array $parameters
    ) {
        $placeholderPath = '/tmp/placeholder.jpg';
        $binaryCreateStruct = new BinaryFileCreateStruct();
        $expectedVariation = $this->createMock(ImageVariation::class);

        $this->ioResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($field->value->id, IORepositoryResolver::VARIATION_ORIGINAL)
            ->willThrowException($this->createMock(NotResolvableException::class));

        $this->placeholderProvider
            ->expects(self::once())
            ->method('getPlaceholder')
            ->with($field->value, $this->placeholderOptions)
            ->willReturn($placeholderPath);

        $this->ioService
            ->expects(self::once())
            ->method('newBinaryCreateStructFromLocalFile')
            ->with($placeholderPath)
            ->willReturn($binaryCreateStruct);

        $this->ioService
            ->expects(self::once())
            ->method('createBinaryFile')
            ->with($binaryCreateStruct);

        $this->aliasGenerator->setPlaceholderProvider(
            $this->placeholderProvider,
            $this->placeholderOptions
        );

        $this->innerAliasGenerator
            ->expects(self::once())
            ->method('getVariation')
            ->with($field, $versionInfo, $variationName, $parameters)
            ->willReturn($expectedVariation);

        $actualVariation = $this->aliasGenerator->getVariation(
            $field,
            $versionInfo,
            $variationName,
            $parameters
        );

        self::assertEquals($field->value->id, $binaryCreateStruct->id);
        self::assertEquals($expectedVariation, $actualVariation);
    }

    /**
     * @dataProvider getVariationProvider
     */
    public function testGetVariationReturnsPlaceholderIfBinaryDataIsNotAvailable(
        Field $field,
        APIVersionInfo $versionInfo,
        string $variationName,
        array $parameters
    ): void {
        $this->aliasGenerator->setVerifyBinaryDataAvailability(true);

        $placeholderPath = '/tmp/placeholder.jpg';
        $binaryCreateStruct = new BinaryFileCreateStruct();
        $expectedVariation = $this->createMock(ImageVariation::class);
        $binaryFile = $this->createMock(BinaryFile::class);

        $this->ioResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($field->value->id, IORepositoryResolver::VARIATION_ORIGINAL)
            ->willReturn('/path/to/original/image.png');

        $this->ioService
            ->method('loadBinaryFile')
            ->with($field->value->id)
            ->willReturn($binaryFile);

        $this->ioService
            ->method('getFileInputStream')
            ->with($binaryFile)
            ->willThrowException($this->createMock(NotFoundException::class));

        $this->placeholderProvider
            ->expects(self::once())
            ->method('getPlaceholder')
            ->with($field->value, $this->placeholderOptions)
            ->willReturn($placeholderPath);

        $this->ioService
            ->expects(self::once())
            ->method('newBinaryCreateStructFromLocalFile')
            ->with($placeholderPath)
            ->willReturn($binaryCreateStruct);

        $this->ioService
            ->expects(self::once())
            ->method('createBinaryFile')
            ->with($binaryCreateStruct);

        $this->aliasGenerator->setPlaceholderProvider(
            $this->placeholderProvider,
            $this->placeholderOptions
        );

        $this->innerAliasGenerator
            ->expects(self::once())
            ->method('getVariation')
            ->with($field, $versionInfo, $variationName, $parameters)
            ->willReturn($expectedVariation);

        $actualVariation = $this->aliasGenerator->getVariation(
            $field,
            $versionInfo,
            $variationName,
            $parameters
        );

        self::assertEquals($field->value->id, $binaryCreateStruct->id);
        self::assertEquals($expectedVariation, $actualVariation);
    }

    /**
     * @dataProvider supportsValueProvider
     */
    public function testSupportsValue(
        Value $value,
        bool $isSupported
    ) {
        self::assertSame($isSupported, $this->aliasGenerator->supportsValue($value));
    }

    public function supportsValueProvider(): array
    {
        return [
            [new NullValue(), false],
            [new ImageValue(), true],
        ];
    }

    public function getVariationProvider(): array
    {
        $field = new Field([
            'value' => new ImageValue([
                'id' => 'images/6/8/4/0/486-10-eng-GB/photo.jpg',
            ]),
        ]);

        return [
            [$field, new VersionInfo(), 'thumbnail', []],
        ];
    }
}
