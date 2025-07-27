<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

class IORepositoryResolverTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $ioService;

    /** @var \Symfony\Component\Routing\RequestContext */
    private $requestContext;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configResolver;

    /** @var \Ibexa\Bundle\Core\Imagine\IORepositoryResolver */
    private $imageResolver;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\FilterConfiguration */
    private $filterConfiguration;

    /** @var \Ibexa\Contracts\Core\Variation\VariationPurger|\PHPUnit\Framework\MockObject\MockObject */
    protected $variationPurger;

    /** @var \Ibexa\Contracts\Core\Variation\VariationPathGenerator|\PHPUnit\Framework\MockObject\MockObject */
    protected $variationPathGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->requestContext = new RequestContext();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->filterConfiguration = new FilterConfiguration();
        $this->filterConfiguration->setConfigResolver($this->configResolver);
        $this->variationPurger = $this->createMock(VariationPurger::class);
        $this->variationPathGenerator = $this->createMock(VariationPathGenerator::class);
        $this->imageResolver = new IORepositoryResolver(
            $this->ioService,
            $this->requestContext,
            $this->filterConfiguration,
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
            ->will(self::returnValue(true));

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
            ->will(self::returnValue(false));

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
            ->expects(self::any())
            ->method('loadBinaryFile')
            ->will(self::returnValue(new BinaryFile(['uri' => $variationPath])));

        $this->variationPathGenerator
            ->expects(self::any())
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
            ->will(self::returnValue(new MissingBinaryFile()));

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
            ->will(self::throwException(new NotFoundException('foo', 'bar')));

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
        $aliasPath = 'Tardis/bigger/in-the-inside/RiverSong_thumbnail.jpg';
        $binary = new Binary('foo content', 'some/mime-type');

        $createStruct = new BinaryFileCreateStruct();
        $this->ioService
            ->expects(self::once())
            ->method('newBinaryCreateStructFromLocalFile')
            ->will(self::returnValue($createStruct));

        $this->ioService
            ->expects(self::once())
            ->method('createBinaryFile');

        $this->imageResolver->store($binary, $path, $filter);
    }

    public function testRemoveEmptyFilters(): void
    {
        $originalPath = 'foo/bar/test.jpg';
        $filters = ['filter1' => true, 'filter2' => true, 'chaud_cacao' => true];

        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->will(self::returnValue($filters));

        $this->variationPathGenerator
            ->expects(self::exactly(count($filters)))
            ->method('getVariationPath')
            ->will(
                self::returnValueMap(
                    [
                        ['foo/bar/test.jpg', 'filter1', 'foo/bar/test_filter1.jpg '],
                        ['foo/bar/test.jpg', 'filter2', 'foo/bar/test_filter2.jpg '],
                        ['foo/bar/test.jpg', 'chaud_cacao', 'foo/bar/test_chaud_cacao.jpg'],
                    ]
                )
            );

        $fileToDelete = 'foo/bar/test_chaud_cacao.jpg';
        $this->ioService
            ->expects(self::exactly(count($filters)))
            ->method('exists')
            ->will(
                self::returnValueMap(
                    [
                        ['foo/bar/test_filter1.jpg', false],
                        ['foo/bar/test_filter2.jpg', false],
                        [$fileToDelete, true],
                    ]
                )
            );

        $binaryFile = new BinaryFile(['id' => $fileToDelete]);
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($fileToDelete)
            ->will(self::returnValue($binaryFile));

        $this->ioService
            ->expects(self::once())
            ->method('deleteBinaryFile')
            ->with($binaryFile);

        $this->imageResolver->remove([$originalPath], []);
    }

    public function testRemoveWithFilters(): void
    {
        $originalPath = 'foo/bar/test.jpg';
        $filters = ['filter1', 'filter2', 'chaud_cacao'];

        $this->configResolver
            ->expects(self::never())
            ->method('getParameter')
            ->with('image_variations')
            ->will(self::returnValue([]));

        $this->variationPathGenerator
            ->expects(self::exactly(count($filters)))
            ->method('getVariationPath')
            ->will(
                self::returnValueMap(
                    [
                        ['foo/bar/test.jpg', 'filter1', 'foo/bar/test_filter1.jpg '],
                        ['foo/bar/test.jpg', 'filter2', 'foo/bar/test_filter2.jpg '],
                        ['foo/bar/test.jpg', 'chaud_cacao', 'foo/bar/test_chaud_cacao.jpg'],
                    ]
                )
            );

        $fileToDelete = 'foo/bar/test_chaud_cacao.jpg';
        $this->ioService
            ->expects(self::exactly(count($filters)))
            ->method('exists')
            ->will(
                self::returnValueMap(
                    [
                        ['foo/bar/test_filter1.jpg', false],
                        ['foo/bar/test_filter2.jpg', false],
                        [$fileToDelete, true],
                    ]
                )
            );

        $binaryFile = new BinaryFile(['id' => $fileToDelete]);
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($fileToDelete)
            ->will(self::returnValue($binaryFile));

        $this->ioService
            ->expects(self::once())
            ->method('deleteBinaryFile')
            ->with($binaryFile);

        $this->imageResolver->remove([$originalPath], $filters);
    }
}
