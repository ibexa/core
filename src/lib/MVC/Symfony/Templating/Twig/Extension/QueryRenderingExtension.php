<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class QueryRenderingExtension extends AbstractExtension
{
    private const VALID_TYPES = ['content', 'location'];

    /** @var FragmentHandler */
    private $fragmentHandler;

    public function __construct(FragmentHandler $fragmentHandler)
    {
        $this->fragmentHandler = $fragmentHandler;
    }

    public function getFunctions(): array
    {
        $typeCallable = function (
            string $type,
            array $options
        ): ?string {
            $this->assertTypeIsValid($type);

            return $this->fragmentHandler->render(
                $this->createControllerReference($options)
            );
        };

        $typeAndRendererCallable = function (
            string $type,
            string $renderer,
            array $options
        ): ?string {
            $this->assertTypeIsValid($type);

            return $this->fragmentHandler->render(
                $this->createControllerReference($options),
                $renderer
            );
        };

        return [
            new TwigFunction(
                'ibexa_render_*_query',
                $typeCallable,
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'ibexa_render_*_query_*',
                $typeAndRendererCallable,
                ['is_safe' => ['html']]
            ),
        ];
    }

    private function createControllerReference(array $options): ControllerReference
    {
        return new ControllerReference('ibexa_query_render::renderQuery', [
            'options' => $options,
        ]);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function assertTypeIsValid(string $type): void
    {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new InvalidArgumentException(
                '$type',
                'Expected value to be of ' . implode(', ', self::VALID_TYPES)
            );
        }
    }
}
