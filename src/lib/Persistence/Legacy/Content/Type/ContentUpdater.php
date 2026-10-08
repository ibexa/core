<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry as Registry;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action;

/**
 * Class to update content objects to a new type version.
 */
class ContentUpdater
{
    /**
     * Content gateway.
     *
     * @var ContentGateway
     */
    protected $contentGateway;

    /**
     * FieldValue converter registry.
     *
     * @var Registry
     */
    protected $converterRegistry;

    /**
     * Storage handler.
     *
     * @var StorageHandler
     */
    protected $storageHandler;

    /** @var ContentMapper */
    protected $contentMapper;

    /**
     * Creates a new content updater.
     *
     * @param ContentGateway $contentGateway
     * @param Registry $converterRegistry
     * @param StorageHandler $storageHandler
     * @param ContentMapper $contentMapper
     */
    public function __construct(
        ContentGateway $contentGateway,
        Registry $converterRegistry,
        StorageHandler $storageHandler,
        ContentMapper $contentMapper
    ) {
        $this->contentGateway = $contentGateway;
        $this->converterRegistry = $converterRegistry;
        $this->storageHandler = $storageHandler;
        $this->contentMapper = $contentMapper;
    }

    /**
     * Determines the necessary update actions.
     *
     * @param Type $fromType
     * @param Type $toType
     *
     * @return Action[]
     */
    public function determineActions(
        Type $fromType,
        Type $toType
    ) {
        $actions = [];
        foreach ($fromType->fieldDefinitions as $fieldDef) {
            if (!$this->hasFieldDefinition($toType, $fieldDef)) {
                $actions[] = new Action\RemoveField(
                    $this->contentGateway,
                    $fieldDef,
                    $this->storageHandler,
                    $this->contentMapper
                );
            }
        }
        foreach ($toType->fieldDefinitions as $fieldDef) {
            if (!$this->hasFieldDefinition($fromType, $fieldDef)) {
                $actions[] = new Action\AddField(
                    $this->contentGateway,
                    $fieldDef,
                    $this->converterRegistry->getConverter(
                        $fieldDef->fieldType
                    ),
                    $this->storageHandler,
                    $this->contentMapper
                );
            }
        }

        return $actions;
    }

    /**
     * hasFieldDefinition.
     *
     * @param Type $type
     * @param FieldDefinition $fieldDef
     *
     * @return bool
     */
    protected function hasFieldDefinition(
        Type $type,
        FieldDefinition $fieldDef
    ) {
        foreach ($type->fieldDefinitions as $existFieldDef) {
            if ($existFieldDef->id == $fieldDef->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Applies all given updates.
     *
     * @param mixed $contentTypeId
     * @param Action[] $actions
     */
    public function applyUpdates(
        $contentTypeId,
        array $actions
    ) {
        if (empty($actions)) {
            return;
        }

        foreach ($this->getContentIdsByContentTypeId($contentTypeId) as $contentId) {
            foreach ($actions as $action) {
                $action->apply($contentId);
            }
        }
    }

    /**
     * Returns all content objects of $contentTypeId.
     *
     * @param mixed $contentTypeId
     *
     * @return int[]
     */
    protected function getContentIdsByContentTypeId($contentTypeId)
    {
        return $this->contentGateway->getContentIdsByContentTypeId($contentTypeId);
    }
}

class_alias(ContentUpdater::class, 'eZ\Publish\Core\Persistence\Legacy\Content\Type\ContentUpdater');
