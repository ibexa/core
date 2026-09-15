<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\View;

use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\MVC\Symfony\View\View;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Core\MVC\Symfony\View\View::class)]
abstract class AbstractViewTestCase extends TestCase
{
    abstract protected function createViewUnderTest($template = null, array $parameters = [], $viewType = 'full'): View;

    /**
     * Returns parameters that are always returned by this view.
     *
     * @return array
     */
    protected function getAlwaysAvailableParams(): array
    {
        return [];
    }

    public function testGetSetParameters(): void
    {
        $params = [
            'bar' => 'baz',
            'fruit' => 'apple',
        ];

        $view = $this->createViewUnderTest('foo');
        $view->setParameters($params);

        self::assertSame($this->getAlwaysAvailableParams() + $params, $view->getParameters());
    }

    public function testAddParameters(): void
    {
        $params = ['bar' => 'baz', 'fruit' => 'apple'];
        $view = $this->createViewUnderTest('foo', $params);

        $additionalParams = ['truc' => 'muche', 'laurel' => 'hardy'];
        $view->addParameters($additionalParams);

        self::assertSame($this->getAlwaysAvailableParams() + $params + $additionalParams, $view->getParameters());
    }

    public function testHasParameter(): View
    {
        $view = $this->createViewUnderTest(__METHOD__, ['foo' => 'bar']);

        self::assertTrue($view->hasParameter('foo'));
        self::assertFalse($view->hasParameter('nonExistent'));

        return $view;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testHasParameter')]
    public function testGetParameter(View $view): View
    {
        self::assertSame('bar', $view->getParameter('foo'));

        return $view;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetParameter')]
    public function testGetParameterFail(View $view): void
    {
        $this->expectException(InvalidArgumentException::class);

        $view->getParameter('nonExistent');
    }

    /**
     * @param string|callable $templateIdentifier
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('goodTemplateIdentifierProvider')]
    public function testSetTemplateIdentifier($templateIdentifier): void
    {
        $contentView = $this->createViewUnderTest();
        $contentView->setTemplateIdentifier($templateIdentifier);

        self::assertSame($templateIdentifier, $contentView->getTemplateIdentifier());
    }

    public static function goodTemplateIdentifierProvider(): array
    {
        return [
            ['foo:bar:baz.html.twig'],
            [
                static function (): string {
                    return 'foo';
                },
            ],
        ];
    }

    /**
     * @param mixed $badTemplateIdentifier
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('badTemplateIdentifierProvider')]
    public function testSetTemplateIdentifierWrongType($badTemplateIdentifier): void
    {
        $this->expectException(InvalidArgumentType::class);

        $contentView = $this->createViewUnderTest();
        $contentView->setTemplateIdentifier($badTemplateIdentifier);
    }

    public static function badTemplateIdentifierProvider(): array
    {
        return [
            [123],
            [true],
            [new \stdClass()],
            [['foo', 'bar']],
        ];
    }
}
