<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation as APIRelation;
use Ibexa\Core\Repository\Values\Content\Relation;

/**
 * Base integration test for field types handling content relations.
 *
 * @group integration
 * @group field-type
 * @group relation
 *
 * @since 6.1
 */
trait RelationSearchBaseIntegrationTestTrait
{
    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    abstract public function getCreateExpectedRelations(Content $content);

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    abstract public function getUpdateExpectedRelations(Content $content);

    /**
     * Tests relation processing on field create.
     */
    public function testCreateContentRelationsProcessedCorrect()
    {
        $content = $this->createContent($this->getValidCreationFieldData());

        $this->assertEquals(
            $this->normalizeRelations(
                $this->getCreateExpectedRelations($content)
            ),
            $this->normalizeRelations(
                $this->getRepository()->getContentService()->loadRelations($content->versionInfo)
            )
        );
    }

    /**
     * Tests relation processing on field update.
     */
    public function testUpdateContentRelationsProcessedCorrect()
    {
        $content = $this->updateContent($this->getValidUpdateFieldData());

        $this->assertEquals(
            $this->normalizeRelations(
                $this->getUpdateExpectedRelations($content)
            ),
            $this->normalizeRelations(
                $this->getRepository()->getContentService()->loadRelations($content->versionInfo)
            )
        );
    }

    /**
     * Normalizes given $relations for easier comparison.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Relation[] $relations
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    protected function normalizeRelations(array $relations): array
    {
        usort(
            $relations,
            static function (APIRelation $a, APIRelation $b): int {
                if ($a->type === $b->type) {
                    return $a->destinationContentInfo->id < $b->destinationContentInfo->id ? 1 : -1;
                }

                return $a->type < $b->type ? 1 : -1;
            }
        );

        return array_map(
            static function (APIRelation $relation): APIRelation {
                return new Relation(
                    [
                        'id' => null,
                        'sourceFieldDefinitionIdentifier' => $relation->sourceFieldDefinitionIdentifier,
                        'type' => $relation->type,
                        'sourceContentInfo' => $relation->sourceContentInfo,
                        'destinationContentInfo' => $relation->destinationContentInfo,
                    ]
                );
            },
            $relations
        );
    }

    public function testCopyContentCopiesFieldRelations()
    {
        $content = $this->updateContent($this->getValidUpdateFieldData());
        $contentService = $this->getRepository()->getContentService();

        $copy = $contentService->copyContent(
            $content->contentInfo,
            new LocationCreateStruct(['parentLocationId' => 2])
        );

        $copy = $contentService->loadContent($copy->id, null, 2);
        $this->assertEquals(
            $this->normalizeRelations(
                $this->getUpdateExpectedRelations($copy)
            ),
            $this->normalizeRelations(
                $this->getRepository()->getContentService()->loadRelations($copy->versionInfo)
            )
        );

        $firstVersion = $contentService->loadContent($copy->id, null, 1);
        $this->assertEquals(
            $this->normalizeRelations(
                $this->getCreateExpectedRelations($firstVersion)
            ),
            $this->normalizeRelations(
                $this->getRepository()->getContentService()->loadRelations($firstVersion->versionInfo)
            )
        );
    }

    public function testSubtreeCopyContentCopiesFieldRelations()
    {
        $contentService = $this->getRepository()->getContentService();
        $locationService = $this->getRepository()->getLocationService();
        $content = $this->updateContent($this->getValidUpdateFieldData());

        $location = $locationService->createLocation(
            $content->getVersionInfo()->getContentInfo(),
            $locationService->newLocationCreateStruct(2)
        );

        $copiedLocation = $locationService->copySubtree(
            $location,
            $locationService->loadLocation(43)
        );

        $copy = $contentService->loadContent($copiedLocation->getContentInfo()->id);
        $this->assertEquals(
            $this->normalizeRelations(
                $this->getCreateExpectedRelations($copy)
            ),
            $this->normalizeRelations(
                $this->getRepository()->getContentService()->loadRelations($copy->versionInfo)
            )
        );
    }
}

class_alias(RelationSearchBaseIntegrationTestTrait::class, 'eZ\Publish\API\Repository\Tests\FieldType\RelationSearchBaseIntegrationTestTrait');
