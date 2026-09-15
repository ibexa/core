<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\View;

use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(ContentView::class)]
#[Group('mvc')]
class ContentViewTest extends AbstractViewTestCase
{
    /**
     * Params that are always returned by this view.
     *
     * @var array
     */
    private $valueParams = ['content' => null];

    #[DataProvider('constructProvider')]
    public function testConstruct($templateIdentifier, array $params)
    {
        $contentView = new ContentView($templateIdentifier, $params);
        self::assertSame($templateIdentifier, $contentView->getTemplateIdentifier());
        self::assertSame($this->valueParams + $params, $contentView->getParameters());
    }

    public static function constructProvider()
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

    #[DataProvider('constructFailProvider')]
    public function testConstructFail($templateIdentifier)
    {
        $this->expectException(InvalidArgumentType::class);

        new ContentView($templateIdentifier);
    }

    public static function constructFailProvider()
    {
        return [
            [123],
            [new \stdClass()],
            [[1, 2, 3]],
        ];
    }

    protected function createViewUnderTest($template = null, array $parameters = [], $viewType = 'full'): View
    {
        return new ContentView($template, $parameters, $viewType);
    }

    protected function getAlwaysAvailableParams(): array
    {
        return $this->valueParams;
    }
}
