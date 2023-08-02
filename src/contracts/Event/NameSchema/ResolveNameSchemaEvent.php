<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Event\NameSchema;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

final class ResolveNameSchemaEvent extends AbstractNameSchemaEvent
{
    private ContentType $contentType;

    private array $fieldMap;

    private array $languageCodes;

    public function __construct(
        array $schemaIdentifiers,
        ContentType $contentType,
        array $fieldMap,
        array $languageCodes
    ) {
        parent::__construct($schemaIdentifiers);
        $this->contentType = $contentType;
        $this->fieldMap = $fieldMap;
        $this->languageCodes = $languageCodes;
    }

    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }

    public function getLanguageCodes(): array
    {
        return $this->languageCodes;
    }
}
