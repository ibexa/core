<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Helper;

use Ibexa\Core\Repository\NameSchema\NameSchemaService as NativeNameSchemaService;

/**
 * @deprecated inject \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface instead.
 * @see \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface
 */
class NameSchemaService extends NativeNameSchemaService
{
}

class_alias(NameSchemaService::class, 'eZ\Publish\Core\Repository\Helper\NameSchemaService');
