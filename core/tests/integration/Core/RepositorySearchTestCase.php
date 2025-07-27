<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core;

use Ibexa\Solr\Handler as SolrHandler;

abstract class RepositorySearchTestCase extends RepositoryTestCase
{
    protected function refreshSearch(): void
    {
        $handler = self::getContainer()->get('ibexa.spi.search');
        if (
            class_exists(SolrHandler::class)
            && $handler instanceof SolrHandler
        ) {
            $handler->commit();
        }
    }
}
