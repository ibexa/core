<?php

declare(strict_types=1);

namespace Ibexa\Bundle\Core\Messenger;

use Ibexa\Bundle\Core\Message\PublishContentAsync;
use Ibexa\Contracts\Messenger\Transport\MessageProviderInterface;

final class MessageProvider implements MessageProviderInterface
{
    public function getHandledClasses(): iterable
    {
        return [PublishContentAsync::class];
    }
}
