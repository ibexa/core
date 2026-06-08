<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Messenger;

use Ibexa\Bundle\Core\Message\PublishContentAsync;
// TODO circular dependency on ibexa/messenger !!!
use Ibexa\Contracts\Messenger\Transport\MessageProviderInterface;

final class MessageProvider implements MessageProviderInterface
{
    public function getHandledClasses(): iterable
    {
        return [PublishContentAsync::class];
    }
}
