<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\VisibilityConverter;

use Ibexa\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverter;
use Ibexa\Core\IO\Flysystem\VisibilityConverter\DFSVisibilityConverter;
use League\Flysystem\Visibility;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DFSVisibilityConverter::class)]
final class DFSVisibilityConverterTest extends BaseVisibilityConverterTestCase
{
    private const DFS_FILE_FLAGS = 0640;
    private const DFS_DIRECTORY_FLAGS = 0750;

    protected function buildVisibilityConverter(): BaseVisibilityConverter
    {
        return new DFSVisibilityConverter(
            $this->innerVisibilityConverterMock,
            [
                'files' => self::DFS_FILE_FLAGS,
                'directories' => self::DFS_DIRECTORY_FLAGS,
            ]
        );
    }

    public static function getDataForTestForFile(): iterable
    {
        yield 'public visibility (from DFS config)' => [
            Visibility::PUBLIC,
            self::DFS_FILE_FLAGS,
        ];
        yield 'private visibility (from Flysystem fallback)' => [
            Visibility::PRIVATE,
            self::FLYSYSTEM_FILE_FLAGS,
        ];
    }

    public static function getDataForTestForDirectory(): iterable
    {
        yield 'public visibility (from DFS config)' => [
            Visibility::PUBLIC,
            self::DFS_DIRECTORY_FLAGS,
        ];
        yield 'private visibility (from Flysystem fallback)' => [
            Visibility::PRIVATE,
            self::FLYSYSTEM_DIRECTORY_FLAGS,
        ];
    }

    public static function getDataForTestInverseForFile(): iterable
    {
        yield self::DFS_FILE_FLAGS . ' (DFS config) is public' => [
            self::DFS_FILE_FLAGS,
            Visibility::PUBLIC,
        ];
        yield self::FLYSYSTEM_FILE_FLAGS . ' (Flysystem fallback) is private' => [
            self::FLYSYSTEM_FILE_FLAGS,
            Visibility::PRIVATE,
        ];
        yield self::DIFFERENT_FILE_FLAGS . ' (Flysystem fallback default) is public' => [
            self::DIFFERENT_FILE_FLAGS,
            Visibility::PUBLIC,
        ];
    }

    public static function getDataForTestInverseForDirectory(): iterable
    {
        yield self::DFS_DIRECTORY_FLAGS . ' (DFS config) is public' => [
            self::DFS_DIRECTORY_FLAGS,
            Visibility::PUBLIC,
        ];
        yield self::FLYSYSTEM_DIRECTORY_FLAGS . ' (Flysystem fallback) is private' => [
            self::FLYSYSTEM_DIRECTORY_FLAGS,
            Visibility::PRIVATE,
        ];
        yield self::DIFFERENT_DIRECTORY_FLAGS . ' (Flysystem fallback default) is public' => [
            self::DIFFERENT_DIRECTORY_FLAGS,
            Visibility::PUBLIC,
        ];
    }
}
