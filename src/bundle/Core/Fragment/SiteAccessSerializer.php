<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Fragment;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class SiteAccessSerializer implements SiteAccessSerializerInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @throws \JsonException
     */
    public function serializeSiteAccessAsControllerAttributes(SiteAccess $siteAccess, ControllerReference $controller): void
    {
        // Serialize the SiteAccess to get it back after. @see \Ibexa\Core\MVC\Symfony\EventListener\SiteAccessMatchListener
        $controller->attributes['serialized_siteaccess'] = json_encode($siteAccess, JSON_THROW_ON_ERROR);
        $controller->attributes['serialized_siteaccess_matcher'] = $this->serializer->serialize(
            $siteAccess->matcher,
            'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder', 'connection']]
        );
        if ($siteAccess->matcher instanceof SiteAccess\Matcher\CompoundInterface) {
            $subMatchers = $siteAccess->matcher->getSubMatchers();
            foreach ($subMatchers as $subMatcher) {
                $controller->attributes['serialized_siteaccess_sub_matchers'][get_class($subMatcher)] = $this->serializer->serialize(
                    $subMatcher,
                    'json',
                    [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder', 'connection']]
                );
            }
        }
    }
}
