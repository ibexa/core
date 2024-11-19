<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\SharedGateway\DatabasePlatform;

final class FallbackGateway extends AbstractGateway
{
}

class_alias(FallbackGateway::class, 'eZ\Publish\Core\Persistence\Legacy\SharedGateway\DatabasePlatform\FallbackGateway');
