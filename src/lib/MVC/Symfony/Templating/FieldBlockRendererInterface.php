<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Templating;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Interface for content fields/fieldDefinitions renderers.
 * Implementors can render view and edit views for fields/fieldDefinitions.
 */
interface FieldBlockRendererInterface
{
    /**
     * Renders the HTML view markup for a given field.
     *
     * @param Field $field
     * @param string $fieldTypeIdentifier FieldType identifier for $field
     * @param array $params An array of parameters to pass to the field view
     *
     * @throws InvalidArgumentException If no field with provided $fieldIdentifier can be found in $content.
     *
     * @return string
     */
    public function renderContentFieldView(
        Field $field,
        $fieldTypeIdentifier,
        array $params = []
    );

    /**
     * Renders the HTML edit markup for a given field.
     *
     * @param Field $field
     * @param string $fieldTypeIdentifier FieldType identifier for $field
     * @param array $params An array of parameters to pass to the field edit view
     *
     * @throws InvalidArgumentException If no field with provided $fieldIdentifier can be found in $content.
     *
     * @return string
     */
    public function renderContentFieldEdit(
        Field $field,
        $fieldTypeIdentifier,
        array $params = []
    );

    /**
     * Renders the HTML view markup for the given field definition.
     *
     * @param FieldDefinition $fieldDefinition
     *
     * @return string
     */
    public function renderFieldDefinitionView(
        FieldDefinition $fieldDefinition,
        array $params = []
    );

    /**
     * Renders the HTML edot markup for the given field definition.
     *
     * @param FieldDefinition $fieldDefinition
     *
     * @return string
     */
    public function renderFieldDefinitionEdit(
        FieldDefinition $fieldDefinition,
        array $params = []
    );
}
