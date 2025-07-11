<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use DateTime;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Tests\Solr\SetupFactory\LegacySetupFactory as LegacySolrSetupFactory;
use RuntimeException;

/**
 * Test case for field filtering operations in the SearchService.
 *
 * @covers \Ibexa\Contracts\Core\Repository\SearchService
 *
 * @group integration
 * @group search
 * @group language_fallback
 *
 * @template TSearchHitValueObject
 *
 * @phpstan-type TIndexMap array{dedicated: string, shared: string, single: string, cloud: string}
 */
class SearchServiceTranslationLanguageFallbackTest extends BaseTestCase
{
    public const SETUP_DEDICATED = 'dedicated';
    public const SETUP_SHARED = 'shared';
    public const SETUP_SINGLE = 'single';
    public const SETUP_CLOUD = 'cloud';

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType
     */
    protected function createTestContentType()
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('test-type');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->names = ['eng-GB' => 'Test type'];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new DateTime();

        $fieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('search_field', 'ibexa_integer');
        $fieldCreate->names = ['eng-GB' => 'Search field'];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = 1;
        $fieldCreate->isTranslatable = true;
        $fieldCreate->isSearchable = true;

        $createStruct->addFieldDefinition($fieldCreate);

        $fieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('sort_field', 'ibexa_integer');
        $fieldCreate->names = ['eng-GB' => 'Sort field'];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = 2;
        $fieldCreate->isTranslatable = false;
        $fieldCreate->isSearchable = true;

        $createStruct->addFieldDefinition($fieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        return $contentType;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType $contentType
     * @param array $searchValuesMap
     * @param string $mainLanguageCode
     * @param bool $alwaysAvailable
     * @param int $sortValue
     * @param array $parentLocationIds
     *
     * @return mixed
     */
    protected function createContent(
        $contentType,
        array $searchValuesMap,
        $mainLanguageCode,
        $alwaysAvailable,
        $sortValue,
        array $parentLocationIds
    ) {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, $mainLanguageCode);
        $contentCreateStruct->alwaysAvailable = $alwaysAvailable;

        foreach ($searchValuesMap as $languageCode => $searchValue) {
            $contentCreateStruct->setField('search_field', $searchValue, $languageCode);
        }

        $contentCreateStruct->setField('sort_field', $sortValue, $mainLanguageCode);

        $data = [];
        $data['content'] = $contentService->publishVersion(
            $contentService->createContent($contentCreateStruct)->versionInfo
        );

        foreach ($parentLocationIds as $parentLocationId) {
            $locationCreateStruct = $locationService->newLocationCreateStruct($parentLocationId);
            $data['locations'][] = $locationService->createLocation(
                $data['content']->contentInfo,
                $locationCreateStruct
            );
        }

        return $data;
    }

    /**
     * @param array $parentLocationIds
     *
     * @return array
     */
    public function createTestContent(array $parentLocationIds)
    {
        $repository = $this->getRepository();
        $languageService = $repository->getContentLanguageService();

        $langCreateStruct = $languageService->newLanguageCreateStruct();
        $langCreateStruct->languageCode = 'por-PT';
        $langCreateStruct->name = 'Portuguese (portuguese)';
        $langCreateStruct->enabled = true;

        $languageService->createLanguage($langCreateStruct);

        $contentType = $this->createTestContentType();

        $context = [
            $repository,
            [
                1 => $this->createContent(
                    $contentType,
                    [
                        'eng-GB' => 1,
                        'ger-DE' => 2,
                        'por-PT' => 3,
                    ],
                    'eng-GB',
                    false,
                    1,
                    $parentLocationIds
                ),
                2 => $this->createContent(
                    $contentType,
                    [
                        //"eng-GB" => ,
                        'ger-DE' => 1,
                        'por-PT' => 2,
                    ],
                    'por-PT',
                    true,
                    2,
                    $parentLocationIds
                ),
                3 => $this->createContent(
                    $contentType,
                    [
                        //"eng-GB" => ,
                        //"ger-DE" => ,
                        'por-PT' => 1,
                    ],
                    'por-PT',
                    false,
                    3,
                    $parentLocationIds
                ),
            ],
        ];

        $this->refreshSearch($repository);

        return $context;
    }

    public function testCreateTestContent()
    {
        return $this->createTestContent([2, 12]);
    }

    public function providerForTestFind()
    {
        $data = [
            0 => [
                [
                    'languages' => [
                        'eng-GB',
                        'ger-DE',
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            1 => [
                [
                    'languages' => [
                        'eng-GB',
                        'por-PT',
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    'languages' => [
                        'ger-DE',
                        'eng-GB',
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            3 => [
                [
                    'languages' => [
                        'ger-DE',
                        'por-PT',
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            4 => [
                [
                    'languages' => [
                        'por-PT',
                        'eng-GB',
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            5 => [
                [
                    'languages' => [
                        'por-PT',
                        'eng-GB',
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            6 => [
                [
                    'languages' => [
                        'eng-GB',
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            7 => [
                [
                    'languages' => [
                        'ger-DE',
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            8 => [
                [
                    'languages' => [
                        'eng-GB',
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            9 => [
                [
                    'languages' => [
                        'por-PT',
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            10 => [
                [
                    'languages' => [
                        'ger-DE',
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            11 => [
                [
                    'languages' => [
                        'por-PT',
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            12 => [
                [
                    'languages' => [
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'localhost:8983/solr/core0',
                        ],
                    ],
                ],
            ],
            13 => [
                [
                    'languages' => [
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            14 => [
                [
                    'languages' => [
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            15 => [
                [
                    'languages' => [
                        'eng-US',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [],
            ],
            16 => [
                [
                    'languages' => [
                        'eng-GB',
                        'ger-DE',
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            17 => [
                [
                    'languages' => [
                        'eng-GB',
                        'por-PT',
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            18 => [
                [
                    'languages' => [
                        'ger-DE',
                        'eng-GB',
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            19 => [
                [
                    'languages' => [
                        'ger-DE',
                        'por-PT',
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            20 => [
                [
                    'languages' => [
                        'por-PT',
                        'eng-GB',
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            21 => [
                [
                    'languages' => [
                        'por-PT',
                        'eng-GB',
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            22 => [
                [
                    'languages' => [
                        'eng-GB',
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            23 => [
                [
                    'languages' => [
                        'ger-DE',
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            24 => [
                [
                    'languages' => [
                        'eng-GB',
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            25 => [
                [
                    'languages' => [
                        'por-PT',
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            26 => [
                [
                    'languages' => [
                        'ger-DE',
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            27 => [
                [
                    'languages' => [
                        'por-PT',
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            28 => [
                [
                    'languages' => [
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core3',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core0',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core3_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            29 => [
                [
                    'languages' => [
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'ger-DE',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            30 => [
                [
                    'languages' => [
                        'por-PT',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        1,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core2',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core2_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            31 => [
                [
                    'languages' => [
                        'eng-US',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core0',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core3_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            32 => [
                [
                    'languages' => [],
                    'useAlwaysAvailable' => true,
                ],
                $mainLanguages = [
                    [
                        1,
                        'eng-GB',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                            self::SETUP_SHARED => 'localhost:8983/solr/core0',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core3_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core0',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core3_shard1_replica(_n)?1/',
                        ],
                    ],
                    [
                        3,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core0',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core3_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
            33 => [
                [
                    'languages' => [],
                    'useAlwaysAvailable' => false,
                ],
                $mainLanguages,
            ],
            34 => [
                [
                    'languages' => [],
                    'useAlwaysAvailable' => true,
                ],
                $mainLanguages,
            ],
            35 => [
                [
                    'languages' => [],
                    'useAlwaysAvailable' => false,
                ],
                $mainLanguages,
            ],
            36 => [
                [
                    'languages' => [],
                ],
                $mainLanguages,
            ],
            37 => [
                [
                    'languages' => [],
                ],
                $mainLanguages,
            ],
            38 => [
                [
                    'languages' => [
                        'eng-US',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [],
            ],
            39 => [
                [
                    'languages' => [
                        'eng-US',
                    ],
                    'useAlwaysAvailable' => true,
                ],
                [
                    [
                        2,
                        'por-PT',
                        [
                            self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                            self::SETUP_SHARED => 'localhost:8983/solr/core0',
                            self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                            self::SETUP_CLOUD => 'http://localhost:8983/solr/core3_shard1_replica(_n)?1/',
                        ],
                    ],
                ],
            ],
        ];

        $setupFactory = $this->getSetupFactory();

        if ($setupFactory instanceof LegacySolrSetupFactory) {
            $data = array_merge(
                $data,
                [
                    [
                        [
                            'languages' => [
                                'eng-GB',
                                'ger-DE',
                            ],
                            'useAlwaysAvailable' => true,
                            'excludeTranslationsFromAlwaysAvailable' => false,
                        ],
                        [
                            [
                                1,
                                'eng-GB',
                                [
                                    self::SETUP_DEDICATED => 'localhost:8983/solr/core0',
                                    self::SETUP_SHARED => 'localhost:8983/solr/core3',
                                    self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                                    self::SETUP_CLOUD => 'http://localhost:8983/solr/core0_shard1_replica(_n)?1/',
                                ],
                                [
                                    self::SETUP_DEDICATED => [
                                        'searchHitIndex' => 0,
                                        'preparedDataTestIndex' => 1,
                                    ],
                                    self::SETUP_SHARED => [
                                        'searchHitIndex' => 0,
                                        'preparedDataTestIndex' => 1,
                                    ],
                                    self::SETUP_SINGLE => [
                                        'searchHitIndex' => 0,
                                        'preparedDataTestIndex' => 1,
                                    ],
                                    self::SETUP_CLOUD => [
                                        'searchHitIndex' => 0,
                                        'preparedDataTestIndex' => 1,
                                    ],
                                ],
                            ],
                            [
                                2,
                                'ger-DE',
                                [
                                    self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                                    self::SETUP_SHARED => 'localhost:8983/solr/core2',
                                    self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                                    self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                                ],
                                [
                                    self::SETUP_DEDICATED => [
                                        'searchHitIndex' => 1,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_SHARED => [
                                        'searchHitIndex' => 1,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_SINGLE => [
                                        'searchHitIndex' => 1,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_CLOUD => [
                                        'searchHitIndex' => 1,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                ],
                            ],
                            [
                                3,
                                'por-PT',
                                [
                                    self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                                    self::SETUP_SHARED => 'localhost:8983/solr/core0',
                                    self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                                    self::SETUP_CLOUD => 'http://localhost:8983/solr/core3_shard1_replica(_n)?1/',
                                ],
                                [
                                    self::SETUP_DEDICATED => [
                                        'searchHitIndex' => 2,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_SHARED => [
                                        'searchHitIndex' => 2,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_SINGLE => [
                                        'searchHitIndex' => 2,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_CLOUD => [
                                        'searchHitIndex' => 2,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        [
                            'languages' => [
                                'ger-DE',
                                'eng-GB',
                            ],
                            'useAlwaysAvailable' => true,
                            'excludeTranslationsFromAlwaysAvailable' => false,
                        ],
                        [
                            [
                                1,
                                'ger-DE',
                                [
                                    self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                                    self::SETUP_SHARED => 'localhost:8983/solr/core2',
                                    self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                                    self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                                ],
                                [
                                    self::SETUP_DEDICATED => [
                                        'searchHitIndex' => 0,
                                        'preparedDataTestIndex' => 1,
                                    ],
                                    self::SETUP_SHARED => [
                                        'searchHitIndex' => 0,
                                        'preparedDataTestIndex' => 1,
                                    ],
                                    self::SETUP_SINGLE => [
                                        'searchHitIndex' => 0,
                                        'preparedDataTestIndex' => 1,
                                    ],
                                    self::SETUP_CLOUD => [
                                        'searchHitIndex' => 0,
                                        'preparedDataTestIndex' => 1,
                                    ],
                                ],
                            ],
                            [
                                2,
                                'ger-DE',
                                [
                                    self::SETUP_DEDICATED => 'localhost:8983/solr/core3',
                                    self::SETUP_SHARED => 'localhost:8983/solr/core2',
                                    self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                                    self::SETUP_CLOUD => 'http://localhost:8983/solr/core1_shard1_replica(_n)?1/',
                                ],
                                [
                                    self::SETUP_DEDICATED => [
                                        'searchHitIndex' => 1,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_SHARED => [
                                        'searchHitIndex' => 1,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_SINGLE => [
                                        'searchHitIndex' => 1,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_CLOUD => [
                                        'searchHitIndex' => 1,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                ],
                            ],
                            [
                                3,
                                'por-PT',
                                [
                                    self::SETUP_DEDICATED => 'localhost:8983/solr/core2',
                                    self::SETUP_SHARED => 'localhost:8983/solr/core0',
                                    self::SETUP_SINGLE => 'localhost:8983/solr/collection1',
                                    self::SETUP_CLOUD => 'http://localhost:8983/solr/core3_shard1_replica(_n)?1/',
                                ],
                                [
                                    self::SETUP_DEDICATED => [
                                        'searchHitIndex' => 2,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_SHARED => [
                                        'searchHitIndex' => 2,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_SINGLE => [
                                        'searchHitIndex' => 2,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                    self::SETUP_CLOUD => [
                                        'searchHitIndex' => 2,
                                        'preparedDataTestIndex' => 2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $data;
    }

    protected function getSetupType()
    {
        if (getenv('SOLR_CLOUD')) {
            return self::SETUP_CLOUD;
        }

        $coresSetup = getenv('CORES_SETUP');
        switch ($coresSetup) {
            case self::SETUP_DEDICATED:
                return self::SETUP_DEDICATED;
            case self::SETUP_SHARED:
                return self::SETUP_SHARED;
            case self::SETUP_SINGLE:
                return self::SETUP_SINGLE;
        }

        throw new RuntimeException("Backend cores setup '{$coresSetup}' is not handled");
    }

    /**
     * @phpstan-param TIndexMap $indexMap
     *
     * @throws \ErrorException
     */
    protected function getIndexName(array $indexMap): ?string
    {
        $setupFactory = $this->getSetupFactory();

        if ($setupFactory instanceof LegacySolrSetupFactory) {
            $setupType = $this->getSetupType();

            return $indexMap[$setupType];
        }

        return null;
    }

    /**
     * @dataProvider providerForTestFind
     *
     * @depends      testCreateTestContent
     *
     * @param array $languageSettings
     * @param array $contentDataList
     * @param array $context
     */
    public function testFindContent(
        array $languageSettings,
        array $contentDataList,
        array $context
    ) {
        /** @var \Ibexa\Contracts\Core\Repository\Repository $repository */
        list($repository, $data) = $context;

        $queryProperties = [
            'filter' => new Criterion\ContentTypeIdentifier('test-type'),
            'sortClauses' => [
                new SortClause\Field('test-type', 'sort_field'),
                new SortClause\Field('test-type', 'search_field'),
            ],
        ];

        $searchResult = $repository->getSearchService()->findContent(
            new Query($queryProperties),
            $languageSettings
        );

        self::assertEquals(count($contentDataList), $searchResult->totalCount);

        foreach ($contentDataList as $index => $contentData) {
            list($contentNo, $translationLanguageCode, $indexMap) = $contentData;
            list($index, $contentNo) = $this->getIndexesToMatchData($contentData, $index, $contentNo);

            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
            $content = $searchResult->searchHits[$index]->valueObject;

            self::assertEquals(
                $data[$contentNo]['content']->id,
                $content->id
            );
            $this->assertIndexName($indexMap, $searchResult->searchHits[$index]);
            self::assertEquals(
                $translationLanguageCode,
                $searchResult->searchHits[$index]->matchedTranslation
            );
        }
    }

    /**
     * @dataProvider providerForTestFind
     *
     * @depends      testCreateTestContent
     *
     * @param array $languageSettings
     * @param array $contentDataList
     * @param array $context
     */
    public function testFindLocationsSingle(
        array $languageSettings,
        array $contentDataList,
        array $context
    ) {
        /** @var \Ibexa\Contracts\Core\Repository\Repository $repository */
        list($repository, $data) = $context;

        $queryProperties = [
            'filter' => new Criterion\LogicalAnd(
                [
                    new Criterion\ContentTypeIdentifier('test-type'),
                    new Criterion\Subtree('/1/2/'),
                ]
            ),
            'sortClauses' => [
                new SortClause\Field('test-type', 'sort_field'),
                new SortClause\Field('test-type', 'search_field'),
            ],
        ];

        $searchResult = $repository->getSearchService()->findLocations(
            new LocationQuery($queryProperties),
            $languageSettings
        );

        self::assertEquals(count($contentDataList), $searchResult->totalCount);

        foreach ($contentDataList as $index => $contentData) {
            list($contentNo, $translationLanguageCode, $indexMap) = $contentData;
            list($index, $contentNo) = $this->getIndexesToMatchData($contentData, $index, $contentNo);

            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location $location */
            $location = $searchResult->searchHits[$index]->valueObject;

            self::assertEquals(
                $data[$contentNo]['locations'][0]->id,
                $location->id
            );
            $this->assertIndexName($indexMap, $searchResult->searchHits[$index]);
            self::assertEquals(
                $translationLanguageCode,
                $searchResult->searchHits[$index]->matchedTranslation
            );
        }
    }

    /**
     * @dataProvider providerForTestFind
     *
     * @depends      testCreateTestContent
     *
     * @param array $languageSettings
     * @param array $contentDataList
     * @param array $context
     */
    public function testFindLocationsMultiple(
        array $languageSettings,
        array $contentDataList,
        array $context
    ) {
        /** @var \Ibexa\Contracts\Core\Repository\Repository $repository */
        list($repository, $data) = $context;

        $queryProperties = [
            'filter' => new Criterion\ContentTypeIdentifier('test-type'),
            'sortClauses' => [
                new SortClause\Location\Depth(Query::SORT_ASC),
                new SortClause\Field('test-type', 'sort_field'),
                new SortClause\Field('test-type', 'search_field'),
            ],
        ];

        $searchResult = $repository->getSearchService()->findLocations(
            new LocationQuery($queryProperties),
            $languageSettings
        );

        self::assertEquals(count($contentDataList) * 2, $searchResult->totalCount);

        foreach ($contentDataList as $index => $contentData) {
            list($contentNo, $translationLanguageCode, $indexMap) = $contentData;
            list($index, $contentNo) = $this->getIndexesToMatchData($contentData, $index, $contentNo);

            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location $location */
            $location = $searchResult->searchHits[$index]->valueObject;

            self::assertEquals(
                $data[$contentNo]['locations'][0]->id,
                $location->id
            );
            $this->assertIndexName($indexMap, $searchResult->searchHits[$index]);
            self::assertEquals(
                $translationLanguageCode,
                $searchResult->searchHits[$index]->matchedTranslation
            );
        }

        foreach ($contentDataList as $index => $contentData) {
            list($contentNo, $translationLanguageCode, $indexMap) = $contentData;
            list($index, $contentNo) = $this->getIndexesToMatchData($contentData, $index, $contentNo);

            $realIndex = $index + count($contentDataList);
            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location $location */
            $location = $searchResult->searchHits[$realIndex]->valueObject;

            self::assertEquals(
                $data[$contentNo]['locations'][1]->id,
                $location->id
            );
            $this->assertIndexName($indexMap, $searchResult->searchHits[$realIndex]);
            self::assertEquals(
                $translationLanguageCode,
                $searchResult->searchHits[$realIndex]->matchedTranslation
            );
        }
    }

    /**
     * @phpstan-param TIndexMap $indexMap
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit<TSearchHitValueObject> $searchHit
     */
    private function assertIndexName(array $indexMap, SearchHit $searchHit): void
    {
        if (!$this->isSolrInMaxVersion('9.3.0')) {
            // In Solr 9.3.0 and later, the shard parameter is not used anymore.
            return;
        }

        $indexName = $this->getIndexName($indexMap);

        if ($indexName === null) {
            self::assertNull($searchHit->index);
        } else {
            self::assertRegExp('~^' . $indexName . '$~', (string)$searchHit->index);
        }
    }

    private function getIndexesToMatchData(
        array $inputContentData,
        int $currentSearchHitIndex,
        int $currentPreparedDataTestIndex
    ): array {
        $indexesToMatchData = [
            $currentSearchHitIndex,
            $currentPreparedDataTestIndex,
        ];

        if ($this->getSetupFactory() instanceof LegacySolrSetupFactory) {
            $setupType = $this->getSetupType();

            if ($customMatchResultIndexData = $inputContentData[3][$setupType] ?? null) {
                // Use custom indexes
                $indexesToMatchData = [
                    $customMatchResultIndexData['searchHitIndex'],
                    $customMatchResultIndexData['preparedDataTestIndex'],
                ];
            }
        }

        return $indexesToMatchData;
    }

    private function isSolrInMaxVersion(string $maxVersion): bool
    {
        $version = getenv('SOLR_VERSION');
        if (is_string($version) && !empty($version)) {
            return version_compare($version, $maxVersion, '<');
        }

        return false;
    }
}
