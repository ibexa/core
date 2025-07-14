<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine;

use Ibexa\Bundle\Core\Imagine\Filter\FilterConfiguration;
use Ibexa\Bundle\Core\Imagine\IORepositoryResolver;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Contracts\Core\Variation\VariationPurger;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Liip\ImagineBundle\Exception\Imagine\Cache\Resolver\NotResolvableException;
use Liip\ImagineBundle\Model\Binary;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\IORepositoryResolver
 */
final class IORepositoryResolverTest extends TestCase
{
    private IOServiceInterface & MockObject $ioService;

    private RequestContext $requestContext;

    private ConfigResolverInterface & MockObject $configResolver;

    private IORepositoryResolver $imageResolver;

    protected VariationPurger & MockObject $variationPurger;

    protected VariationPathGenerator & MockObject $variationPathGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->requestContext = new RequestContext();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $filterConfiguration = new FilterConfiguration();
        $filterConfiguration->setConfigResolver($this->configResolver);
        $this->variationPurger = $this->createMock(VariationPurger::class);
        $this->variationPathGenerator = $this->createMock(VariationPathGenerator::class);
        $this->imageResolver = new IORepositoryResolver(
            $this->ioService,
            $this->requestContext,
            $filterConfiguration,
            $this->variationPurger,
            $this->variationPathGenerator
        );
    }

    /**
     * @dataProvider getFilePathProvider
     */
    public function testGetFilePath(string $path, string $filter, string $expected): void
    {
        $this->variationPathGenerator
            ->expects(self::once())
            ->method('getVariationPath')
            ->with($path, $filter)
            ->willReturn($expected);
        self::assertSame($expected, $this->imageResolver->getFilePath($path, $filter));
    }

    /**
     * @return array<array{string, string, string}>
     */
    public function getFilePathProvider(): array
    {
        return [
            ['Tardis/bigger/in-the-inside/RiverSong.jpg', 'thumbnail', 'Tardis/bigger/in-the-inside/RiverSong_thumbnail.jpg'],
            ['Tardis/bigger/in-the-inside/RiverSong', 'foo', 'Tardis/bigger/in-the-inside/RiverSong_foo'],
            ['CultOfScaro/Dalek-fisherman.png', 'so_ridiculous', 'CultOfScaro/Dalek-fisherman_so_ridiculous.png'],
            ['CultOfScaro/Dalek-fisherman', 'so_ridiculous', 'CultOfScaro/Dalek-fisherman_so_ridiculous'],
        ];
    }

    public function testIsStoredImageExists(): void
    {
        $filter = 'thumbnail';
        $path = 'Tardis/bigger/in-the-inside/RiverSong.jpg';
        $aliasPath = 'Tardis/bigger/in-the-inside/RiverSong_thumbnail.jpg';

        $this->variationPathGenerator
            ->expects(self::once())
            ->method('getVariationPath')
            ->with($path, $filter)
            ->willReturn($aliasPath);

        $this->ioService
            ->expects(self::once())
            ->method('exists')
            ->with($aliasPath)
            ->willReturn(true);

        self::assertTrue($this->imageResolver->isStored($path, $filter));
    }

    public function testIsStoredImageDoesntExist(): void
    {
        $filter = 'thumbnail';
        $path = 'Tardis/bigger/in-the-inside/RiverSong.jpg';
        $aliasPath = 'Tardis/bigger/in-the-inside/RiverSong_thumbnail.jpg';

        $this->variationPathGenerator
            ->expects(self::once())
            ->method('getVariationPath')
            ->with($path, $filter)
            ->willReturn($aliasPath);

        $this->ioService
            ->expects(self::once())
            ->method('exists')
            ->with($aliasPath)
            ->willReturn(false);

        self::assertFalse($this->imageResolver->isStored($path, $filter));
    }

    /**
     * @dataProvider resolveProvider
     */
    public function testResolve(string $path, string $filter, string $variationPath, ?string $requestUrl, mixed $expected): void
    {
        if ($requestUrl) {
            $this->requestContext->fromRequest(Request::create($requestUrl));
        }

        $this->ioService
            ->expects(self::atLeastOnce())
            ->method('loadBinaryFile')
            ->willReturn(new BinaryFile(['uri' => $variationPath]));

        $this->variationPathGenerator
            ->expects($filter !== IORepositoryResolver::VARIATION_ORIGINAL ? self::once() : self::never())
            ->method('getVariationPath')
            ->willReturn($variationPath);

        $result = $this->imageResolver->resolve($path, $filter);
        self::assertSame($expected, $result);
    }

    public function testResolveMissing(): void
    {
        $this->expectException(NotResolvableException::class);

        $path = 'foo/something.jpg';
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($path)
            ->willReturn(new MissingBinaryFile());

        $this->imageResolver->resolve($path, 'some_filter');
    }

    public function testResolveNotFound(): void
    {
        $this->expectException(NotResolvableException::class);

        $path = 'foo/something.jpg';
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($path)
            ->willThrowException(new NotFoundException('foo', 'bar'));

        $this->imageResolver->resolve($path, 'some_filter');
    }

    /**
     * @return array<int, array{string, string, string, string|null, string}>
     */
    public function resolveProvider(): array
    {
        return [
            [
                'Tardis/bigger/in-the-inside/RiverSong.jpg',
                IORepositoryResolver::VARIATION_ORIGINAL,
                '/var/doctorwho/storage/images/Tardis/bigger/in-the-inside/RiverSong.jpg',
                null,
                'http://localhost/var/doctorwho/storage/images/Tardis/bigger/in-the-inside/RiverSong.jpg',
            ],
            [
                'Tardis/bigger/in-the-inside/RiverSong.jpg',
                'thumbnail',
                '/var/doctorwho/storage/images/Tardis/bigger/in-the-inside/RiverSong_thumbnail.jpg',
                null,
                'http://localhost/var/doctorwho/storage/images/Tardis/bigger/in-the-inside/RiverSong_thumbnail.jpg',
            ],
            [
                'Tardis/bigger/in-the-inside/RiverSong.jpg',
                'thumbnail',
                '/var/doctorwho/storage/images/Tardis/bigger/in-the-inside/RiverSong_thumbnail.jpg',
                'http://localhost',
                'http://localhost/var/doctorwho/storage/images/Tardis/bigger/in-the-inside/RiverSong_thumbnail.jpg',
            ],
            [
                'CultOfScaro/Dalek-fisherman.png',
                'so_ridiculous',
                '/var/doctorwho/storage/images/CultOfScaro/Dalek-fisherman_so_ridiculous.png',
                'http://doctor.who:7890',
                'http://doctor.who:7890/var/doctorwho/storage/images/CultOfScaro/Dalek-fisherman_so_ridiculous.png',
            ],
            [
                'CultOfScaro/Dalek-fisherman.png',
                'so_ridiculous',
                '/var/doctorwho/storage/images/CultOfScaro/Dalek-fisherman_so_ridiculous.png',
                'https://doctor.who',
                'https://doctor.who/var/doctorwho/storage/images/CultOfScaro/Dalek-fisherman_so_ridiculous.png',
            ],
            [
                'CultOfScaro/Dalek-fisherman.png',
                'so_ridiculous',
                '/var/doctorwho/storage/images/CultOfScaro/Dalek-fisherman_so_ridiculous.png',
                'https://doctor.who:1234',
                'https://doctor.who:1234/var/doctorwho/storage/images/CultOfScaro/Dalek-fisherman_so_ridiculous.png',
            ],
            [
                'CultOfScaro/Dalek-fisherman.png',
                IORepositoryResolver::VARIATION_ORIGINAL,
                '/var/doctorwho/storage/images/CultOfScaro/Dalek-fisherman.png',
                'https://doctor.who:1234',
                'https://doctor.who:1234/var/doctorwho/storage/images/CultOfScaro/Dalek-fisherman.png',
            ],
        ];
    }

    public function testStore(): void
    {
        $filter = 'thumbnail';
        $path = 'Tardis/bigger/in-the-inside/RiverSong.jpg';
        $binary = new Binary('foo content', 'some/mime-type');

        $createStruct = new BinaryFileCreateStruct();
        $this->ioService
            ->expects(self::once())
            ->method('newBinaryCreateStructFromLocalFile')
            ->willReturn($createStruct);

        $this->ioService
            ->expects(self::once())
            ->method('createBinaryFile');

        $this->imageResolver->store($binary, $path, $filter);
    }

    /**
     * @return iterable<string, array{string[], array<string, bool>}>
     */
    public function getDataForTestRemove(): iterable
    {
        yield 'empty filters' => [
            [],
            ['filter1' => true, 'filter2' => true, 'chaud_cacao' => true],
        ];

        yield 'with filters' => [
            ['filter1', 'filter2', 'chaud_cacao'],
            [],
        ];
    }

    /**
     * @dataProvider getDataForTestRemove
     *
     * @param string[] $filters
     * @param array<string, bool> $imageVariations
     */
    public function testRemove(array $filters, array $imageVariations): void
    {
        $originalPath = 'foo/bar/test.jpg';

        $this->configResolver
            ->expects(!empty($filters) ? self::never() : self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn($imageVariations);

        $pathCount = !empty($filters) ? count($filters) : count($imageVariations);
        $this->variationPathGenerator
            ->expects(self::exactly($pathCount))
            ->method('getVariationPath')
            ->willReturnMap(
                [
                    ['foo/bar/test.jpg', 'filter1', 'foo/bar/test_filter1.jpg'],
                    ['foo/bar/test.jpg', 'filter2', 'foo/bar/test_filter2.jpg'],
                    ['foo/bar/test.jpg', 'chaud_cacao', 'foo/bar/test_chaud_cacao.jpg'],
                ]
            );

        $this->configureIoServiceForDeletingBinaryFile($pathCount);

        $this->imageResolver->remove([$originalPath], $filters);
    }

    private function configureIoServiceForDeletingBinaryFile(int $pathCount): void
    {
        $fileToDelete = 'foo/bar/test_chaud_cacao.jpg';
        $this->ioService
            ->expects(self::exactly($pathCount))
            ->method('exists')
            ->willReturnMap(
                [
                    ['foo/bar/test_filter1.jpg', false],
                    ['foo/bar/test_filter2.jpg', false],
                    [$fileToDelete, true],
                ]
            );

        $binaryFile = new BinaryFile(['id' => $fileToDelete]);
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($fileToDelete)
            ->willReturn($binaryFile);

        $this->ioService
            ->expects(self::once())
            ->method('deleteBinaryFile')
            ->with($binaryFile);
    }
}
