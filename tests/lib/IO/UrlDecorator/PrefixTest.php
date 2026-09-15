<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\UrlDecorator;

use Ibexa\Core\IO\IOConfigProvider;
use Ibexa\Core\IO\UrlDecorator;
use Ibexa\Core\IO\UrlDecorator\Prefix;
use PHPUnit\Framework\TestCase;

class PrefixTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideData')]
    public function testDecorate($url, $prefix, $decoratedUrl)
    {
        $decorator = $this->buildDecorator($prefix);

        self::assertEquals(
            $decoratedUrl,
            $decorator->decorate($url)
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideData')]
    public function testUndecorate($url, $prefix, $decoratedUrl)
    {
        $decorator = $this->buildDecorator($prefix);

        self::assertEquals(
            $url,
            $decorator->undecorate($decoratedUrl)
        );
    }

    protected function buildDecorator(string $prefix): UrlDecorator
    {
        $ioConfigResolverMock = $this->createMock(IOConfigProvider::class);
        $ioConfigResolverMock
            ->method('getLegacyUrlPrefix')
            ->willReturn($prefix);

        return new Prefix($ioConfigResolverMock);
    }

    public static function provideData()
    {
        return [
            [
                'images/file.png',
                'var/storage',
                'var/storage/images/file.png',
            ],
            [
                'images/file.png',
                'var/storage/',
                'var/storage/images/file.png',
            ],
            [
                'images/file.png',
                'http://static.example.com',
                'http://static.example.com/images/file.png',
            ],
        ];
    }
}
