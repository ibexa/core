<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\IO;

use Ibexa\Core\IO\UrlDecorator;
use Ibexa\Core\IO\UrlRedecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UrlRedecoratorTest extends TestCase
{
    /** @var UrlRedecorator|MockObject */
    private $redecorator;

    /** @var UrlDecorator|MockObject */
    private $sourceDecoratorMock;

    /** @var UrlDecorator|MockObject */
    private $targetDecoratorMock;

    protected function setUp(): void
    {
        $this->redecorator = new UrlRedecorator(
            $this->sourceDecoratorMock = $this->createMock(UrlDecorator::class),
            $this->targetDecoratorMock = $this->createMock(UrlDecorator::class)
        );
    }

    public function testRedecorateFromSource()
    {
        $this->sourceDecoratorMock
            ->expects(self::once())
            ->method('undecorate')
            ->with('http://static.example.com/images/file.png')
            ->will(self::returnValue('images/file.png'));

        $this->targetDecoratorMock
            ->expects(self::once())
            ->method('decorate')
            ->with('images/file.png')
            ->will(self::returnValue('/var/test/storage/images/file.png'));

        self::assertEquals(
            '/var/test/storage/images/file.png',
            $this->redecorator->redecorateFromSource('http://static.example.com/images/file.png')
        );
    }

    public function testRedecorateFromTarget()
    {
        $this->targetDecoratorMock
            ->expects(self::once())
            ->method('undecorate')
            ->with('/var/test/storage/images/file.png')
            ->will(self::returnValue('images/file.png'));

        $this->sourceDecoratorMock
            ->expects(self::once())
            ->method('decorate')
            ->with('images/file.png')
            ->will(self::returnValue('http://static.example.com/images/file.png'));

        self::assertEquals(
            'http://static.example.com/images/file.png',
            $this->redecorator->redecorateFromTarget('/var/test/storage/images/file.png')
        );
    }
}
