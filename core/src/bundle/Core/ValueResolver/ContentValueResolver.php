<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\ValueResolver;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class ContentValueResolver implements ValueResolverInterface
{
    private const string ATTRIBUTE_CONTENT_ID = 'contentId';

    public function __construct(
        private readonly ContentService $contentService
    ) {
    }

    /**
     * @return iterable<\Ibexa\Contracts\Core\Repository\Values\Content\Content>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Content::class) {
            return [];
        }

        $contentId = $request->attributes->get(self::ATTRIBUTE_CONTENT_ID);

        if (!is_numeric($contentId)) {
            return [];
        }

        yield $this->contentService->loadContent((int)$contentId);
    }
}
