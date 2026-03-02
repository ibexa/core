<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common;

use Ibexa\Contracts\Core\Search\FieldType;

/**
 * Generator for search backend field names.
 */
class FieldNameGenerator
{
    /**
     * `$fieldNameMapping` maps internal search field type identifiers to backend
     * suffixes (e.g. `ibexa_string` => `s`).
     *
     * `$fallbackPrefixes` defines type prefixes for generic fallback normalization
     * when no explicit mapping exists (e.g. `ibexa_dense_vector_<suffix>`).
     *
     * @param array<string, string> $fieldNameMapping
     * @param string[] $fallbackPrefixes
     */
    public function __construct(
        protected array $fieldNameMapping,
        private readonly array $fallbackPrefixes = []
    ) {
    }

    /**
     * Get name for document field.
     *
     * Consists of a name, and optionally field name and a content type name.
     *
     * @param string $name
     * @param string|null $field
     * @param string|null $type
     *
     * @return string
     */
    public function getName($name, $field = null, $type = null): string
    {
        return implode('_', array_filter([$type, $field, $name]));
    }

    /**
     * Map field type.
     *
     * For indexing backend the following scheme will always be used for names:
     * {name}_{type}.
     *
     * Using dynamic fields this allows to define fields either depending on
     * types, or names.
     *
     * Only the field with the name 'id' remains untouched.
     */
    public function getTypedName(string $name, FieldType $type): string
    {
        if ($name === 'id') {
            return $name;
        }

        $typeIdentifier = $type->getType();
        $typeName = $this->fieldNameMapping[$typeIdentifier] ?? $this->normalizeUsingFallbackPrefixes($typeIdentifier);

        return $name . '_' . $typeName;
    }

    /**
     * Generic fallback for field type families that encode backend suffix in the type identifier.
     *
     * Example:
     * - `ibexa_dense_vector_gemini_embedding_001_1536_dv` => `gemini_embedding_001_1536_dv`
     */
    private function normalizeUsingFallbackPrefixes(string $typeIdentifier): string
    {
        foreach ($this->fallbackPrefixes as $prefix) {
            if (str_starts_with($typeIdentifier, $prefix)) {
                return substr($typeIdentifier, strlen($prefix));
            }
        }

        return $typeIdentifier;
    }
}
