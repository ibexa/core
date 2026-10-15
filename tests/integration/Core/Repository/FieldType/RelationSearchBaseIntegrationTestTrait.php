<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation as APIRelation;
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
     * @return APIRelation[]
     */
    abstract public function getCreateExpectedRelations(Content $content): array;

    /**
     * @param Content $content
     *
     * @return APIRelation[]
     */
    abstract public function getUpdateExpectedRelations(Content $content): array;

    /**
     * Tests relation processing on field create.
     */
    public function testCreateContentRelationsProcessedCorrect(): void
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
    public function testUpdateContentRelationsProcessedCorrect(): void
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
     * @param APIRelation[] $relations
     *
     * @return APIRelation[]
     */
    protected function normalizeRelations(array $relations): array
    {
        usort(
            $relations,
            static function (
                APIRelation $a,
                APIRelation $b
            ): int {
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
                        'id' => -1,
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

    public function testCopyContentCopiesFieldRelations(): void
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

    public function testSubtreeCopyContentCopiesFieldRelations(): void
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
     * @return APIRelation[]
     */
    private function getRelations(RelationList $relationList): array
    {
        return array_filter(array_map(
            static fn (RelationListItemInterface $relationListItem): ?APIRelation => $relationListItem->getRelation(),
            $relationList->items
        ));
    }
}
