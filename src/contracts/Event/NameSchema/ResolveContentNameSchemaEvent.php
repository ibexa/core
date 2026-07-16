<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Event\NameSchema;

use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

final class ResolveContentNameSchemaEvent extends AbstractNameSchemaEvent implements ContentAwareEventInterface
{
    private Content $content;

    /**
     * @param array<string, array<string>> $schemaIdentifiers
     * @param array<int|string, array<string, Value>>  $fieldMap
     * @param array<string> $languageCodes
     */
    public function __construct(
        Content $content,
        array $schemaIdentifiers,
        ContentType $contentType,
        array $fieldMap,
        array $languageCodes
    ) {
        parent::__construct($schemaIdentifiers, $contentType, $fieldMap, $languageCodes);
        $this->content = $content;
    }

    public function getContent(): Content
    {
        return $this->content;
    }
}
