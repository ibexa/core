<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\View;

use Closure;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\View;

/**
 * @group mvc
 *
 * @covers \Ibexa\Core\MVC\Symfony\View\ContentView
 */
class ContentViewTest extends AbstractViewTestCase
{
    /**
     * Params that are always returned by this view.
     */
    private array $valueParams = ['content' => null];

    /**
     * @dataProvider constructProvider
     */
    public function testConstruct(string|Closure $templateIdentifier, array $params): void
    {
        $contentView = new ContentView($templateIdentifier, $params);
        self::assertSame($templateIdentifier, $contentView->getTemplateIdentifier());
        self::assertSame($this->valueParams + $params, $contentView->getParameters());
    }

    public function constructProvider(): array
    {
        return [
            ['some:valid:identifier', ['foo' => 'bar']],
            ['another::identifier', []],
            ['oops:i_did_it:again', ['singer' => 'Britney Spears']],
            [
                static function (): bool {
                    return true;
                },
                [],
            ],
            [
                static function (): bool {
                    return true;
                },
                ['truc' => 'muche'],
            ],
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    protected function createViewUnderTest(string|Closure $template = null, array $parameters = [], $viewType = 'full'): View
    {
        return new ContentView($template, $parameters, $viewType);
    }

    protected function getAlwaysAvailableParams(): array
    {
        return $this->valueParams;
    }
}
