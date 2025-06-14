<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Issue EZP-21906.
 */
class EZP21906SearchOneContentMultipleLocationsTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();

        // Adding locations for content #58 ("Contact Us").
        // We first need to create "containers" since only one location of a content can exist at a time under the same parent.
        $contentCreateStruct1 = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );
        $contentCreateStruct1->setField('name', 'EZP-21906-1');
        $draft1 = $contentService->createContent(
            $contentCreateStruct1,
            [$locationService->newLocationCreateStruct(2)]
        );
        $folder1 = $contentService->publishVersion($draft1->versionInfo);
        $locationsFolder1 = iterator_to_array($locationService->loadLocations($folder1->contentInfo));

        $contentCreateStruct2 = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );
        $contentCreateStruct2->setField('name', 'EZP-21906-2');
        $draft2 = $contentService->createContent(
            $contentCreateStruct2,
            [$locationService->newLocationCreateStruct(2)]
        );
        $folder2 = $contentService->publishVersion($draft2->versionInfo);
        $locationsFolder2 = iterator_to_array($locationService->loadLocations($folder2->contentInfo));

        $feedbackFormContentInfo = $contentService->loadContentInfo(58);
        $locationCreateStruct1 = $locationService->newLocationCreateStruct($locationsFolder1[0]->id);
        $locationService->createLocation($feedbackFormContentInfo, $locationCreateStruct1);
        $locationCreateStruct2 = $locationService->newLocationCreateStruct($locationsFolder2[0]->id);
        $locationService->createLocation($feedbackFormContentInfo, $locationCreateStruct2);

        $this->refreshSearch($repository);
    }

    /**
     * @dataProvider searchContentQueryProvider
     */
    public function testSearchContentMultipleLocations(Query $query, $expectedResultCount)
    {
        $result = $this->getRepository()->getSearchService()->findContent($query);
        self::assertSame($expectedResultCount, $result->totalCount);
        self::assertSame($expectedResultCount, count($result->searchHits));
    }

    public function searchContentQueryProvider()
    {
        return [
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('feedback_form'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                    ]
                ),
                1,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('feedback_form'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                        'sortClauses' => [new SortClause\ContentName()],
                    ]
                ),
                1,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('feedback_form'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                    ]
                ),
                1,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('feedback_form'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                        'sortClauses' => [new SortClause\ContentName(Query::SORT_DESC)],
                    ]
                ),
                1,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('feedback_form'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                    ]
                ),
                1,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('folder'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                        'sortClauses' => [new SortClause\ContentName()],
                    ]
                ),
                2,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('folder'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                        'sortClauses' => [new SortClause\ContentName(Query::SORT_DESC)],
                    ]
                ),
                2,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('folder'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                    ]
                ),
                2,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('folder'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                    ]
                ),
                2,
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Subtree('/1/2/'),
                                new Criterion\ContentTypeIdentifier('product'),
                                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                            ]
                        ),
                    ]
                ),
                0,
            ],
        ];
    }
}
