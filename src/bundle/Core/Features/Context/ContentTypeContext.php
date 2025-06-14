<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions as ApiExceptions;
use PHPUnit\Framework\Assert as Assertion;

/**
 * Sentences for content types.
 */
class ContentTypeContext implements Context
{
    /**
     * Default ContentTypeGroup.
     */
    public const DEFAULT_GROUP = 'Content';

    /**
     * Default language code.
     */
    public const DEFAULT_LANGUAGE = 'eng-GB';

    /** @var \Ibexa\Contracts\Core\Repository\ContentTypeService */
    protected $contentTypeService;

    public function __construct(ContentTypeService $contentTypeService)
    {
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * @Given (that) a content type exists with identifier :identifier with fields:
     * @Given (that) a content type exists with identifier :identifier in Group with identifier :groupIdentifier with fields:
     *     |   Identifier   |     Type       |     Name      |
     *     |  title         |  ibexa_string      |  Title        |
     *     |  body          |  ezxml         |  Body         |
     *
     * Makes sure a content type with $identifier and with the provided $fields definition.
     */
    public function ensureContentTypeWithIndentifier(
        $identifier,
        TableNode $fields,
        $groupIdentifier = self::DEFAULT_GROUP
    ) {
        $identifier = strtolower($identifier);
        $contentType = $this->loadContentTypeByIdentifier($identifier, false);

        if (!$contentType) {
            $contentType = $this->createContentType($groupIdentifier, $identifier, $fields);
        }

        return $contentType;
    }

    /**
     * @Given (that) a content type does not exist with identifier :identifier
     *
     * Makes sure a content type with $identifier does not exist.
     * If it exists deletes it.
     */
    public function ensureContentTypeDoesntExist($identifier)
    {
        $contentType = $this->loadContentTypeByIdentifier($identifier, false);
        if ($contentType) {
            $this->removeContentType($contentType);
        }
    }

    /**
     * @Then content type (with identifier) :identifier exists
     *
     * Verifies that a content type with $identifier exists.
     */
    public function assertContentTypeExistsByIdentifier($identifier)
    {
        Assertion::assertTrue(
            $this->checkContentTypeExistenceByIdentifier($identifier),
            "Couldn't find a content type with identifier '$identifier'."
        );
    }

    /**
     * @Then content type (with identifier) :identifier does not exist
     *
     * Verifies that a content type with $identifier does not exist.
     */
    public function assertContentTypeDoesntExistsByIdentifier($identifier)
    {
        Assertion::assertFalse(
            $this->checkContentTypeExistenceByIdentifier($identifier),
            "Found a content type with identifier '$identifier'."
        );
    }

    /**
     * @Then content type (with identifier) :identifier exists in Group with identifier :groupIdentifier
     *
     * Verifies that a content type with $identifier exists in group with identifier $groupIdentifier.
     */
    public function assertContentTypeExistsByIdentifierOnGroup($identifier, $groupIdentifier)
    {
        Assertion::assertTrue(
            $this->checkContentTypeExistenceByIdentifier($identifier, $groupIdentifier),
            "Couldn't find content type with identifier '$identifier' on '$groupIdentifier."
        );
    }

    /**
     * Load and return a content type by its identifier.
     *
     * @param  string  $identifier       Content type identifier
     * @param  bool $throwIfNotFound  if true, throws an exception if it is not found.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup|null
     */
    protected function loadContentTypeByIdentifier($identifier, $throwIfNotFound = true)
    {
        $contentType = null;
        try {
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier($identifier);
        } catch (ApiExceptions\NotFoundException $e) {
            $notFoundException = $e;
        }

        if (!$contentType && $throwIfNotFound) {
            throw $notFoundException;
        }

        return $contentType;
    }

    /**
     * Creates a content type with $identifier on content type group with identifier $groupIdentifier and with the
     * given 'fields' definitions.
     *
     * @param  string $groupIdentifier Content type group identifier
     * @param  string $identifier      Content type identifier
     * @param  array $fields           Content type fields definitions
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType
     */
    public function createContentType($groupIdentifier, $identifier, $fields)
    {
        $contentTypeService = $this->contentTypeService;
        $contentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier($groupIdentifier);
        // convert 'some_type' to 'Some Type';
        $contentTypeName = ucwords(str_replace('_', ' ', $identifier));

        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct($identifier);
        $contentTypeCreateStruct->mainLanguageCode = self::DEFAULT_LANGUAGE;
        $contentTypeCreateStruct->names = [self::DEFAULT_LANGUAGE => $contentTypeName];

        $fieldPosition = 0;
        foreach ($fields as $field) {
            $field = array_change_key_case($field, CASE_LOWER);
            $fieldPosition += 10;

            $fieldCreateStruct = $contentTypeService
                ->newFieldDefinitionCreateStruct($field['identifier'], $field['type']);
            $fieldCreateStruct->names = [self::DEFAULT_LANGUAGE => $field['name']];
            $fieldCreateStruct->position = $fieldPosition;
            if (isset($field['required'])) {
                $fieldCreateStruct->isRequired = ($field['required'] === 'true');
            }
            if (isset($field['validator']) && $field['validator'] !== 'false') {
                $fieldCreateStruct->validatorConfiguration = $this->processValidator($field['validator']);
            }
            if (isset($field['settings']) && $field['settings'] !== 'false') {
                $fieldCreateStruct->fieldSettings = $this->processSettings($field['settings']);
            }
            $contentTypeCreateStruct->addFieldDefinition($fieldCreateStruct);
        }

        $contentTypeDraft = $contentTypeService->createContentType(
            $contentTypeCreateStruct,
            [$contentTypeGroup]
        );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $contentType = $contentTypeService->loadContentTypeByIdentifier($identifier);

        return $contentType;
    }

    /**
     * Remove the given 'ContentType' object.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType $contentType
     */
    protected function removeContentType($contentType)
    {
        try {
            $this->contentTypeService->deleteContentType($contentType);
        } catch (ApiExceptions\NotFoundException $e) {
            // nothing to do
        }
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType $contentType
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup $contentTypeGroup
     */
    protected function assignContentGroupTypeToContentType($contentType, $contentTypeGroup)
    {
        try {
            $this->contentTypeService->assignContentTypeGroup($contentType, $contentTypeGroup);
        } catch (ApiExceptions\InvalidArgumentException $exception) {
            //do nothing
        }
    }

    /**
     * Verifies that a content type with $identifier exists.
     *
     * @param string $identifier
     *
     * @return bool
     */
    protected function checkContentTypeExistenceByIdentifier($identifier, $groupIdentifier = null): bool
    {
        $contentType = $this->loadContentTypeByIdentifier($identifier, false);
        if ($contentType && $groupIdentifier) {
            $contentTypeGroups = $contentType->getContentTypeGroups();
            foreach ($contentTypeGroups as $contentTypeGroup) {
                if ($contentTypeGroup->identifier == $groupIdentifier) {
                    return true;
                }
            }

            return false;
        }

        return $contentType ? true : false;
    }
}
