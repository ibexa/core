<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Flysystem\VisibilityConverter;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverter;
use Ibexa\Core\IO\Flysystem\VisibilityConverter\SiteAccessAwareVisibilityConverter;
use League\Flysystem\Visibility;

/**
 * @covers \Ibexa\Core\IO\Flysystem\VisibilityConverter\SiteAccessAwareVisibilityConverter
 */
final class SiteAccessAwareVisibilityConverterTest extends BaseVisibilityConverterTestCase
{
    private const int SITE_FILE_FLAGS = 0644;
    private const int SITE_DIRECTORY_FLAGS = 0755;

    protected function buildVisibilityConverter(): BaseVisibilityConverter
    {
        $configResolverMock = $this->createMock(ConfigResolverInterface::class);
        $configResolverMock
            ->method('getParameter')
            ->willReturnMap(
                [
                    ['io.permissions.files', null, null, self::SITE_FILE_FLAGS],
                    ['io.permissions.directories', null, null, self::SITE_DIRECTORY_FLAGS],
                ]
            );

        return new SiteAccessAwareVisibilityConverter(
            $this->innerVisibilityConverterMock,
            $configResolverMock
        );
    }

    public function getDataForTestForFile(): iterable
    {
        yield 'public visibility (from SiteAccess config)' => [
            Visibility::PUBLIC,
            self::SITE_FILE_FLAGS,
        ];
        yield 'private visibility (from Flysystem fallback)' => [
            Visibility::PRIVATE,
            self::FLYSYSTEM_FILE_FLAGS,
        ];
    }

    public function getDataForTestForDirectory(): iterable
    {
        yield 'public visibility (from SiteAccess config)' => [
            Visibility::PUBLIC,
            self::SITE_DIRECTORY_FLAGS,
        ];
        yield 'private visibility (from Flysystem fallback)' => [
            Visibility::PRIVATE,
            self::FLYSYSTEM_DIRECTORY_FLAGS,
        ];
    }

    public function getDataForTestInverseForFile(): iterable
    {
        yield self::SITE_FILE_FLAGS . ' (SiteAccess config) is public' => [
            self::SITE_FILE_FLAGS,
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

    public function getDataForTestInverseForDirectory(): iterable
    {
        yield self::SITE_DIRECTORY_FLAGS . ' (SiteAccess config) is public' => [
            self::SITE_DIRECTORY_FLAGS,
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
