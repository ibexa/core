<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\VisibilityConverter;

use Ibexa\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter as FlysystemVisibilityConverter;
use League\Flysystem\Visibility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverter
 */
abstract class BaseVisibilityConverterTestCase extends TestCase
{
    protected const FLYSYSTEM_FILE_FLAGS = 0600;
    protected const FLYSYSTEM_DIRECTORY_FLAGS = 0700;
    protected const DIFFERENT_FILE_FLAGS = 0604;
    protected const DIFFERENT_DIRECTORY_FLAGS = 0704;

    protected BaseVisibilityConverter $visibilityConverter;

    /** @var VisibilityConverter&MockObject */
    protected FlysystemVisibilityConverter $innerVisibilityConverterMock;

    abstract protected function buildVisibilityConverter(): BaseVisibilityConverter;

    abstract public function getDataForTestForFile(): iterable;

    abstract public function getDataForTestForDirectory(): iterable;

    abstract public function getDataForTestInverseForFile(): iterable;

    abstract public function getDataForTestInverseForDirectory(): iterable;

    final protected function setUp(): void
    {
        $this->innerVisibilityConverterMock = $this->createMock(FlysystemVisibilityConverter::class);
        $this->innerVisibilityConverterMock
            ->method('forFile')
            ->with(Visibility::PRIVATE)
            ->willReturn(self::FLYSYSTEM_FILE_FLAGS);
        $this->innerVisibilityConverterMock
            ->method('forDirectory')
            ->with(Visibility::PRIVATE)
            ->willReturn(self::FLYSYSTEM_DIRECTORY_FLAGS);

        $this->visibilityConverter = $this->buildVisibilityConverter();
    }

    /**
     * @dataProvider getDataForTestForFile
     */
    final public function testForFile(
        string $visibility,
        int $expectedVisibilityFlags
    ): void {
        self::assertSame(
            $expectedVisibilityFlags,
            $this->visibilityConverter->forFile($visibility)
        );
    }

    /**
     * @dataProvider getDataForTestForDirectory
     */
    final public function testForDirectory(
        string $visibility,
        int $expectedVisibilityFlags
    ): void {
        self::assertSame(
            $expectedVisibilityFlags,
            $this->visibilityConverter->forDirectory($visibility)
        );
    }

    /**
     * @dataProvider getDataForTestInverseForFile
     */
    final public function testInverseForFile(
        int $fileVisibilityFlags,
        string $expectedVisibility
    ): void {
        self::assertSame(
            $expectedVisibility,
            $this->visibilityConverter->inverseForFile($fileVisibilityFlags)
        );
    }

    /**
     * @dataProvider getDataForTestInverseForDirectory
     */
    final public function testInverseForDirectory(
        int $directoryVisibilityFlags,
        string $expectedVisibility
    ): void {
        self::assertSame(
            $expectedVisibility,
            $this->visibilityConverter->inverseForDirectory($directoryVisibilityFlags)
        );
    }

    final public function testDefaultForDirectories(): void
    {
        $this->innerVisibilityConverterMock
            ->expects(self::once())
            ->method('defaultForDirectories')
            ->willReturn(self::FLYSYSTEM_DIRECTORY_FLAGS);

        self::assertSame(
            self::FLYSYSTEM_DIRECTORY_FLAGS,
            $this->visibilityConverter->defaultForDirectories()
        );
    }
}
