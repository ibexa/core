<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

use Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase;
use Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase\QueryBuilder;
use Ibexa\Core\Repository\ContentService;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Request;

// Register ClockMock for Request class before any tests are run
// https://github.com/symfony/symfony/issues/28259
ClockMock::register(Request::class);

// Register ClockMock, as otherwise they are mocked until first method call.
ClockMock::register(DoctrineDatabase::class);
ClockMock::register(ContentService::class);
ClockMock::register(QueryBuilder::class);

require_once __DIR__ . '/vendor/autoload.php';
