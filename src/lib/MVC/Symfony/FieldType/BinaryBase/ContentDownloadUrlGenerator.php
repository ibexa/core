<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\FieldType\BinaryBase;

use Ibexa\Contracts\Core\FieldType\BinaryBase\RouteAwarePathGenerator;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Symfony\Component\Routing\RouterInterface;

class ContentDownloadUrlGenerator implements RouteAwarePathGenerator
{
    /** @var \Symfony\Component\Routing\RouterInterface */
    private $router;

    /** @var string */
    private $route = 'ibexa.content.download.field_id';

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getStoragePathForField(Field $field, VersionInfo $versionInfo): string
    {
        return $this->generate($this->route, $this->getParameters($field, $versionInfo));
    }

    public function generate(string $route, ?array $parameters = []): string
    {
        return $this->router->generate($route, $parameters ?? []);
    }

    public function getRoute(Field $field, VersionInfo $versionInfo): string
    {
        return $this->route;
    }

    public function getParameters(Field $field, VersionInfo $versionInfo): array
    {
        return [
            'contentId' => $versionInfo->contentInfo->id,
            'fieldId' => $field->id,
            'version' => $versionInfo->versionNo,
        ];
    }
}
