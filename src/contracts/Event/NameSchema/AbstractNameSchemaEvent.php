<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Event\NameSchema;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

abstract class AbstractNameSchemaEvent extends AbstractSchemaEvent
{
    private ContentType $contentType;

    /** @var array<string, array<string, \Ibexa\Contracts\Core\FieldType\FieldType>> */
    private array $fieldMap;

    /** @var array<string> */
    private array $languageCodes;

    /**
     * @param array<string, array<string, string>> $schemaIdentifiers
     * @param array<string, array<string, \Ibexa\Contracts\Core\FieldType\FieldType>> $fieldMap
     * @param array<string> $languageCodes
     */
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

    /** @return array<string, array<string, \Ibexa\Contracts\Core\FieldType\FieldType>> */
    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }

    /** @return array<string> */
    public function getLanguageCodes(): array
    {
        return $this->languageCodes;
    }
}
