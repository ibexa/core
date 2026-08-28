<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\MinkExtension\Context\MinkContext;
use Ibexa\Behat\API\Context\ContentContext as ApiContentContext;
use Ibexa\Behat\API\Context\TestContext;
use Ibexa\Behat\Core\Context\ConfigurationContext;
use Ibexa\Behat\Core\Context\FileContext;
use Ibexa\Bundle\Core\Features\Context\ConsoleContext;
use Ibexa\Bundle\Core\Features\Context\ContentContext;
use Ibexa\Bundle\Core\Features\Context\ContentPreviewContext;
use Ibexa\Bundle\Core\Features\Context\ExceptionContext;
use Ibexa\Bundle\Core\Features\Context\QueryControllerContext;

return (new Config())
    ->withProfile((new Profile('core'))
        ->withSuite((new Suite('console'))
            ->withContexts(ConsoleContext::class)
            ->withPaths('vendor/ibexa/core/src/bundle/Core/Features/Console'))
        ->withSuite((new Suite('web'))
            ->withContexts(
                ContentPreviewContext::class,
                ContentContext::class,
                ExceptionContext::class
            )
            ->withPaths(
                'vendor/ibexa/core/src/bundle/Core/Features/Content',
                'vendor/ibexa/core/src/bundle/Core/Features/Exception'
            ))
        ->withSuite((new Suite('query_controller'))
            ->withContexts(
                MinkContext::class,
                ApiContentContext::class,
                TestContext::class,
                ConfigurationContext::class,
                QueryControllerContext::class
            )
            ->withPaths('vendor/ibexa/core/src/bundle/Core/Features/QueryController/query_controller.feature'))
        ->withSuite((new Suite('setup'))
            ->withContexts(
                ApiContentContext::class,
                TestContext::class,
                ConfigurationContext::class,
                FileContext::class
            )
            ->withPaths('vendor/ibexa/core/src/bundle/Core/Features/QueryController/setup.feature')));
