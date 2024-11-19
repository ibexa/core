<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\DataAttributesExtension;
use Twig\Test\IntegrationTestCase;

class DataAttributesExtensionTest extends IntegrationTestCase
{
    public function getExtensions(): array
    {
        return [
            new DataAttributesExtension(),
        ];
    }

    protected function getFixturesDir(): string
    {
        if (PHP_VERSION_ID < 8_00_00) {
            return __DIR__ . '/_fixtures/filters/7.4';
        }

        return __DIR__ . '/_fixtures/filters/8.0';
    }
}

class_alias(DataAttributesExtensionTest::class, 'eZ\Publish\Core\MVC\Symfony\Templating\Tests\Twig\Extension\DataAttributesExtensionTest');
