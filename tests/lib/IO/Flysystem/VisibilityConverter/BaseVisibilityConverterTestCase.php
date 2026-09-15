<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\VisibilityConverter;

use Ibexa\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter as FlysystemVisibilityConverter;
use League\Flysystem\Visibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BaseVisibilityConverter::class)]
abstract class BaseVisibilityConverterTestCase extends TestCase
{
    protected const FLYSYSTEM_FILE_FLAGS = 0600;
    protected const FLYSYSTEM_DIRECTORY_FLAGS = 0700;
    protected const DIFFERENT_FILE_FLAGS = 0604;
    protected const DIFFERENT_DIRECTORY_FLAGS = 0704;

    protected BaseVisibilityConverter $visibilityConverter;

    /** @var \League\Flysystem\UnixVisibility\VisibilityConverter&\PHPUnit\Framework\MockObject\MockObject */
    protected FlysystemVisibilityConverter $innerVisibilityConverterMock;

    abstract protected function buildVisibilityConverter(): BaseVisibilityConverter;

    abstract public static function getDataForTestForFile(): iterable;

    abstract public static function getDataForTestForDirectory(): iterable;

    abstract public static function getDataForTestInverseForFile(): iterable;

    abstract public static function getDataForTestInverseForDirectory(): iterable;

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

    #[DataProvider('getDataForTestForFile')]
    final public function testForFile(string $visibility, int $expectedVisibilityFlags): void
    {
        self::assertSame(
            $expectedVisibilityFlags,
            $this->visibilityConverter->forFile($visibility)
        );
    }

    #[DataProvider('getDataForTestForDirectory')]
    final public function testForDirectory(string $visibility, int $expectedVisibilityFlags): void
    {
        self::assertSame(
            $expectedVisibilityFlags,
            $this->visibilityConverter->forDirectory($visibility)
        );
    }

    #[DataProvider('getDataForTestInverseForFile')]
    final public function testInverseForFile(int $fileVisibilityFlags, string $expectedVisibility): void
    {
        self::assertSame(
            $expectedVisibility,
            $this->visibilityConverter->inverseForFile($fileVisibilityFlags)
        );
    }

    #[DataProvider('getDataForTestInverseForDirectory')]
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
