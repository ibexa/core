<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation as RelationContract;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface;
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
                $this->getRelations(
                    $this->getRepository()->getContentService()->loadRelationList($content->versionInfo)
                )
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
                $this->getRelations(
                    $this->getRepository()->getContentService()->loadRelationList($content->versionInfo)
                )
            )
        );
    }

    /**
     * Normalizes given $relations for easier comparison.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Relation[] $relations
     *
     * @return \Ibexa\Core\Repository\Values\Content\Relation[]
     */
    protected function normalizeRelations(array $relations)
    {
        usort(
            $relations,
            static function (RelationContract $a, RelationContract $b): int {
                if ($a->type == $b->type) {
                    return $a->destinationContentInfo->id < $b->destinationContentInfo->id ? 1 : -1;
                }

                return $a->type < $b->type ? 1 : -1;
            }
        );
        $normalized = array_map(
            static function (RelationContract $relation) {
                $newRelation = new Relation(
                    [
                        'id' => -1,
                        'sourceFieldDefinitionIdentifier' => $relation->sourceFieldDefinitionIdentifier,
                        'type' => $relation->type,
                        'sourceContentInfo' => $relation->sourceContentInfo,
                        'destinationContentInfo' => $relation->destinationContentInfo,
                    ]
                );

                return $newRelation;
            },
            $relations
        );

        return $normalized;
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
                $this->getRelations(
                    $this->getRepository()->getContentService()->loadRelationList($copy->versionInfo)
                )
            )
        );

        $firstVersion = $contentService->loadContent($copy->id, null, 1);
        $this->assertEquals(
            $this->normalizeRelations(
                $this->getCreateExpectedRelations($firstVersion)
            ),
            $this->normalizeRelations(
                $this->getRelations(
                    $this->getRepository()->getContentService()->loadRelationList($firstVersion->versionInfo)
                )
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
                $this->getRelations(
                    $this->getRepository()->getContentService()->loadRelationList($copy->versionInfo)
                )
            )
        );
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    private function getRelations(RelationList $relationList): array
    {
        return array_filter(array_map(
            static fn (RelationListItemInterface $relationListItem): ?RelationContract => $relationListItem->getRelation(),
            $relationList->items
        ));
    }
}
