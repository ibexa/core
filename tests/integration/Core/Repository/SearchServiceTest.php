<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use function count;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Tests\Solr\SetupFactory\LegacySetupFactory as LegacySolrSetupFactory;

/**
 * Test case for operations in the SearchService.
 *
 * @covers \Ibexa\Contracts\Core\Repository\SearchService
 *
 * @group integration
 * @group search
 */
class SearchServiceTest extends BaseTestCase
{
    public const QUERY_CLASS = Query::class;

    public const FIND_CONTENT_METHOD = 'findContent';
    public const FIND_LOCATION_METHOD = 'findLocations';

    public const AVAILABLE_FIND_METHODS = [
        self::FIND_CONTENT_METHOD,
        self::FIND_LOCATION_METHOD,
    ];

    public function getFilterContentSearches()
    {
        $fixtureDir = $this->getFixtureDir();

        return [
            0 => [
                [
                    'filter' => new Criterion\ContentId(
                        [1, 4, 10]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'ContentId.php',
            ],
            1 => [
                [
                    'filter' => new Criterion\LogicalAnd(
                        [
                            new Criterion\ContentId(
                                [1, 4, 10]
                            ),
                            new Criterion\ContentId(
                                [4, 12]
                            ),
                        ]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LogicalAnd.php',
            ],
            2 => [
                [
                    'filter' => new Criterion\LogicalOr(
                        [
                            new Criterion\ContentId(
                                [1, 4, 10]
                            ),
                            new Criterion\ContentId(
                                [4, 12]
                            ),
                        ]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LogicalOr.php',
            ],
            3 => [
                [
                    'filter' => new Criterion\LogicalAnd(
                        [
                            new Criterion\ContentId(
                                [1, 4, 10]
                            ),
                            new Criterion\LogicalNot(
                                new Criterion\ContentId(
                                    [10, 12]
                                )
                            ),
                        ]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LogicalNot.php',
            ],
            4 => [
                [
                    'filter' => new Criterion\LogicalAnd(
                        [
                            new Criterion\ContentId(
                                [1, 4, 10]
                            ),
                            new Criterion\LogicalAnd(
                                [
                                    new Criterion\LogicalNot(
                                        new Criterion\ContentId(
                                            [10, 12]
                                        )
                                    ),
                                ]
                            ),
                        ]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LogicalNot.php',
            ],
            5 => [
                [
                    'filter' => new Criterion\ContentTypeId(
                        4
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'ContentTypeId.php',
            ],
            6 => [
                [
                    'filter' => new Criterion\ContentTypeIdentifier(
                        'user'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'ContentTypeId.php',
            ],
            7 => [
                [
                    'filter' => new Criterion\ContentTypeIdentifier(
                        'user',
                        'invalid'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'ContentTypeId.php',
            ],
            8 => [
                [
                    'filter' => new Criterion\ContentTypeIdentifier(
                        'invalid1',
                        'invalid2'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'MatchNone.php',
            ],
            9 => [
                [
                    'filter' => new Criterion\MatchNone(),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'MatchNone.php',
            ],
            10 => [
                [
                    'filter' => new Criterion\ContentTypeGroupId(
                        2
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'ContentTypeGroupId.php',
            ],
            11 => [
                [
                    'filter' => new Criterion\DateMetadata(
                        Criterion\DateMetadata::MODIFIED,
                        Criterion\Operator::GT,
                        1343140540
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'DateMetadataGt.php',
            ],
            12 => [
                [
                    'filter' => new Criterion\DateMetadata(
                        Criterion\DateMetadata::MODIFIED,
                        Criterion\Operator::GTE,
                        1311154215
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'DateMetadataGte.php',
            ],
            13 => [
                [
                    'filter' => new Criterion\DateMetadata(
                        Criterion\DateMetadata::MODIFIED,
                        Criterion\Operator::LTE,
                        1311154215
                    ),
                    'limit' => 10,
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'DateMetadataLte.php',
            ],
            14 => [
                [
                    'filter' => new Criterion\DateMetadata(
                        Criterion\DateMetadata::MODIFIED,
                        Criterion\Operator::IN,
                        [1033920794, 1060695457, 1343140540]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'DateMetadataIn.php',
            ],
            15 => [
                [
                    'filter' => new Criterion\DateMetadata(
                        Criterion\DateMetadata::MODIFIED,
                        Criterion\Operator::BETWEEN,
                        [1033920776, 1072180276]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'DateMetadataBetween.php',
            ],
            16 => [
                [
                    'filter' => new Criterion\DateMetadata(
                        Criterion\DateMetadata::CREATED,
                        Criterion\Operator::BETWEEN,
                        [1033920776, 1072180278]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'DateMetadataCreated.php',
            ],
            17 => [
                [
                    'filter' => new Criterion\CustomField(
                        'user_group_name_value_s',
                        Criterion\Operator::EQ,
                        'Members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'Field.php',
            ],
            18 => [
                [
                    'filter' => new Criterion\CustomField(
                        'user_group_name_value_s',
                        Criterion\Operator::CONTAINS,
                        'Members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'Field.php',
            ],
            19 => [
                [
                    'filter' => new Criterion\CustomField(
                        'user_group_name_value_s',
                        Criterion\Operator::LT,
                        'Members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'CustomFieldLt.php',
            ],
            20 => [
                [
                    'filter' => new Criterion\CustomField(
                        'user_group_name_value_s',
                        Criterion\Operator::LTE,
                        'Members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'CustomFieldLte.php',
            ],
            21 => [
                [
                    'filter' => new Criterion\CustomField(
                        'user_group_name_value_s',
                        Criterion\Operator::GT,
                        'Members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'CustomFieldGt.php',
            ],
            22 => [
                [
                    'filter' => new Criterion\CustomField(
                        'user_group_name_value_s',
                        Criterion\Operator::GTE,
                        'Members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'CustomFieldGte.php',
            ],
            23 => [
                [
                    'filter' => new Criterion\CustomField(
                        'user_group_name_value_s',
                        Criterion\Operator::BETWEEN,
                        ['Administrator users', 'Members']
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'CustomFieldBetween.php',
            ],
            24 => [
                [
                    'filter' => new Criterion\RemoteId(
                        ['f5c88a2209584891056f987fd965b0ba', 'faaeb9be3bd98ed09f606fc16d144eca']
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'RemoteId.php',
            ],
            25 => [
                [
                    'filter' => new Criterion\SectionId(
                        [2]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'SectionId.php',
            ],
            26 => [
                [
                    'filter' => new Criterion\Field(
                        'name',
                        Criterion\Operator::EQ,
                        'Members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'Field.php',
            ],
            27 => [
                [
                    'filter' => new Criterion\Field(
                        'name',
                        Criterion\Operator::IN,
                        ['Members', 'Anonymous users']
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FieldIn.php',
            ],
            28 => [
                [
                    'filter' => new Criterion\DateMetadata(
                        Criterion\DateMetadata::MODIFIED,
                        Criterion\Operator::BETWEEN,
                        [1033920275, 1033920794]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FieldBetween.php',
            ],
            29 => [
                [
                    'filter' => new Criterion\LogicalOr(
                        [
                            new Criterion\Field(
                                'name',
                                Criterion\Operator::EQ,
                                'Members'
                            ),
                            new Criterion\DateMetadata(
                                Criterion\DateMetadata::MODIFIED,
                                Criterion\Operator::BETWEEN,
                                [1033920275, 1033920794]
                            ),
                        ]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FieldOr.php',
            ],
            30 => [
                [
                    'filter' => new Criterion\Subtree(
                        '/1/5/'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'Subtree.php',
            ],
            31 => [
                [
                    'filter' => new Criterion\LocationId(
                        [1, 2, 5]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LocationId.php',
            ],
            32 => [
                [
                    'filter' => new Criterion\ParentLocationId(
                        [1]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'ParentLocationId.php',
            ],
            33 => [
                [
                    'filter' => new Criterion\LocationRemoteId(
                        ['3f6d92f8044aed134f32153517850f5a', 'f3e90596361e31d496d4026eb624c983']
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LocationRemoteId.php',
            ],
            34 => [
                [
                    // There is no Status Criterion anymore, this should match all published as well
                    'filter' => new Criterion\Subtree(
                        '/1/'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'Status.php',
                // Result having the same sort level should be sorted between them to be system independent
                static function (&$data) {
                    usort(
                        $data->searchHits,
                        static function ($a, $b): int {
                            if ($a->score == $b->score) {
                                if ($a->valueObject['id'] == $b->valueObject['id']) {
                                    return 0;
                                }

                                // Order by ascending ID
                                return ($a->valueObject['id'] < $b->valueObject['id']) ? -1 : 1;
                            }

                            // Order by descending score
                            return ($a->score > $b->score) ? -1 : 1;
                        }
                    );
                },
            ],
            35 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::MODIFIER,
                        Criterion\Operator::EQ,
                        14
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            36 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::MODIFIER,
                        Criterion\Operator::IN,
                        [14]
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            37 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::OWNER,
                        Criterion\Operator::EQ,
                        14
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            38 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::OWNER,
                        Criterion\Operator::IN,
                        [14]
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            39 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::GROUP,
                        Criterion\Operator::EQ,
                        12
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            40 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::GROUP,
                        Criterion\Operator::IN,
                        [12]
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            41 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::GROUP,
                        Criterion\Operator::EQ,
                        4
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            42 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::GROUP,
                        Criterion\Operator::IN,
                        [4]
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            43 => [
                [
                    'filter' => new Criterion\UserMetadata(
                        Criterion\UserMetadata::GROUP,
                        null,
                        [4]
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserMetadata.php',
            ],
            44 => [
                [
                    'filter' => new Criterion\Ancestor(
                        [
                            '/1/5/44/',
                            '/1/5/44/45/',
                        ]
                    ),
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'AncestorContent.php',
            ],
        ];
    }

    public function getContentQuerySearches()
    {
        $fixtureDir = $this->getFixtureDir();

        return [
            [
                [
                    'filter' => new Criterion\ContentId(
                        [58, 10]
                    ),
                    'query' => new Criterion\FullText('contact'),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FullTextFiltered.php',
            ],
            [
                [
                    'query' => new Criterion\FullText(
                        'contact',
                        [
                            'boost' => [
                                'title' => 2,
                            ],
                            'fuzziness' => .5,
                        ]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FullText.php',
            ],
            [
                [
                    'query' => new Criterion\FullText(
                        'Contact*'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FullTextWildcard.php',
            ],
            [
                [
                    'query' => new Criterion\LanguageCode('eng-GB', false),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LanguageCode.php',
            ],
            [
                [
                    'query' => new Criterion\LanguageCode(['eng-US', 'eng-GB']),
                    'offset' => 10,
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LanguageCodeIn.php',
            ],
            [
                [
                    'query' => new Criterion\LanguageCode('eng-GB'),
                    'offset' => 10,
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'LanguageCodeAlwaysAvailable.php',
            ],
            [
                [
                    'query' => new Criterion\Visibility(
                        Criterion\Visibility::VISIBLE
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'Visibility.php',
            ],
            [
                [
                    'query' => new Criterion\IsContainer(true),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 5,
                ],
                $fixtureDir . 'IsContainerTrue.php',
            ],
            [
                [
                    'query' => new Criterion\IsContainer(false),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 5,
                ],
                $fixtureDir . 'IsContainerFalse.php',
            ],
            [
                [
                    'query' => new Criterion\IsUserEnabled(true),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 5,
                ],
                $fixtureDir . 'IsUserEnabledTrue.php',
            ],
            [
                [
                    'query' => new Criterion\IsUserEnabled(false),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 5,
                ],
                $fixtureDir . 'IsUserEnabledFalse.php',
            ],
            [
                [
                    'query' => new Criterion\IsUserBased(true),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'IsUserBasedTrue.php',
            ],
            [
                [
                    'query' => new Criterion\IsUserBased(false),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 1,
                ],
                $fixtureDir . 'IsUserBasedFalse.php',
            ],
            [
                [
                    'query' => new Criterion\UserId(14),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserIdEq.php',
            ],
            [
                [
                    'query' => new Criterion\UserId([14, 10]),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserIdIn.php',
            ],
            [
                [
                    'query' => new Criterion\UserLogin('*adm*', Operator::LIKE),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserLogin.php',
            ],
            [
                [
                    'query' => new Criterion\UserLogin('admin', Operator::EQ),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserLogin.php',
            ],
            [
                [
                    'query' => new Criterion\UserLogin(['admin'], Operator::IN),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserLogin.php',
            ],
            [
                [
                    'query' => new Criterion\UserEmail('*anonymous*', Operator::LIKE),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserEmail.php',
            ],
            [
                [
                    'query' => new Criterion\UserEmail('anonymous@link.invalid', Operator::EQ),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserEmail.php',
            ],
            [
                [
                    'query' => new Criterion\UserEmail(['anonymous@link.invalid'], Operator::IN),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'UserEmail.php',
            ],
            [
                [
                    'query' => new Criterion\SectionIdentifier('users'),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'SectionIdentifier.php',
            ],
            [
                [
                    'query' => new Criterion\SectionIdentifier(['users']),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'SectionIdentifier.php',
            ],
            [
                [
                    'query' => new Criterion\ObjectStateIdentifier('not_locked'),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'ObjectStateIdentifier.php',
            ],
            [
                [
                    'query' => new Criterion\ObjectStateIdentifier(['not_locked']),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'ObjectStateIdentifier.php',
            ],
            [
                [
                    'query' => new Criterion\ObjectStateIdentifier(['not_locked'], 'ibexa_lock'),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'ObjectStateIdentifier.php',
            ],
            [
                [
                    'query' => new Criterion\Sibling(58, 1),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'Sibling.php',
            ],
        ];
    }

    public function getLocationQuerySearches()
    {
        $fixtureDir = $this->getFixtureDir();

        return [
            [
                [
                    'query' => new Criterion\Location\Depth(Criterion\Operator::EQ, 1),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'Depth.php',
            ],
            [
                [
                    'query' => new Criterion\Location\Depth(Criterion\Operator::IN, [1, 3]),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'DepthIn.php',
            ],
            [
                [
                    'query' => new Criterion\Location\Depth(Criterion\Operator::GT, 2),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'DepthGt.php',
            ],
            [
                [
                    'query' => new Criterion\Location\Depth(Criterion\Operator::GTE, 2),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'DepthGte.php',
            ],
            [
                [
                    'query' => new Criterion\Location\Depth(Criterion\Operator::LT, 2),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'Depth.php',
            ],
            [
                [
                    'query' => new Criterion\Location\Depth(Criterion\Operator::LTE, 2),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'DepthLte.php',
            ],
            [
                [
                    'query' => new Criterion\Location\Depth(Criterion\Operator::BETWEEN, [1, 2]),
                    'sortClauses' => [new SortClause\ContentId()],
                    'limit' => 50,
                ],
                $fixtureDir . 'DepthLte.php',
            ],
            [
                [
                    'filter' => new Criterion\Ancestor('/1/5/44/45/'),
                    'sortClauses' => [
                        new SortClause\Location\Depth(),
                    ],
                    'limit' => 50,
                ],
                $fixtureDir . 'AncestorLocation.php',
            ],
        ];
    }

    /**
     * Test for the findContent() method.
     *
     * @dataProvider getFilterContentSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testFindContentFiltered($queryData, $fixture, $closure = null)
    {
        $query = new Query($queryData);
        $this->assertQueryFixture($query, $fixture, $closure);
    }

    /**
     * Test for the findContentInfo() method.
     *
     * @dataProvider getFilterContentSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContentInfo()
     */
    public function testFindContentInfoFiltered($queryData, $fixture, $closure = null)
    {
        $query = new Query($queryData);
        $this->assertQueryFixture($query, $fixture, $this->getContentInfoFixtureClosure($closure), true);
    }

    /**
     * Test for the findLocations() method.
     *
     * @dataProvider getFilterContentSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFindLocationsContentFiltered($queryData, $fixture, $closure = null)
    {
        $query = new LocationQuery($queryData);
        $this->assertQueryFixture($query, $fixture, $closure);
    }

    /**
     * Test for the findContent() method.
     *
     * @dataProvider getContentQuerySearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testQueryContent($queryData, $fixture, $closure = null)
    {
        $query = new Query($queryData);
        $this->assertQueryFixture($query, $fixture, $closure);
    }

    /**
     * Test for the findContentInfo() method.
     *
     * @dataProvider getContentQuerySearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testQueryContentInfo($queryData, $fixture, $closure = null)
    {
        $query = new Query($queryData);
        $this->assertQueryFixture($query, $fixture, $this->getContentInfoFixtureClosure($closure), true);
    }

    /**
     * Test for the findLocations() method.
     *
     * @dataProvider getContentQuerySearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testQueryContentLocations($queryData, $fixture, $closure = null)
    {
        $query = new LocationQuery($queryData);
        $this->assertQueryFixture($query, $fixture, $closure);
    }

    /**
     * Test for the findLocations() method.
     *
     * @dataProvider getLocationQuerySearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testQueryLocations($queryData, $fixture, $closure = null)
    {
        $query = new LocationQuery($queryData);
        $this->assertQueryFixture($query, $fixture, $closure);
    }

    public function getCaseInsensitiveSearches()
    {
        return [
            [
                [
                    'filter' => new Criterion\Field(
                        'name',
                        Criterion\Operator::EQ,
                        'Members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
            ],
            [
                [
                    'filter' => new Criterion\Field(
                        'name',
                        Criterion\Operator::EQ,
                        'members'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
            ],
            [
                [
                    'filter' => new Criterion\Field(
                        'name',
                        Criterion\Operator::EQ,
                        'MEMBERS'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
            ],
        ];
    }

    /**
     * Test for the findContent() method.
     *
     * @dataProvider getCaseInsensitiveSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testFindContentFieldFiltersCaseSensitivity($queryData)
    {
        $query = new Query($queryData);
        $this->assertQueryFixture(
            $query,
            $this->getFixtureDir() . 'Field.php'
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @dataProvider getCaseInsensitiveSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFindLocationsFieldFiltersCaseSensitivity($queryData)
    {
        $query = new LocationQuery($queryData);
        $this->assertQueryFixture(
            $query,
            $this->getFixtureDir() . 'Field.php'
        );
    }

    public function getRelationFieldFilterSearches()
    {
        $fixtureDir = $this->getFixtureDir();

        return [
            0 => [
                [
                    'filter' => new Criterion\FieldRelation(
                        'image',
                        Criterion\Operator::IN,
                        [1, 4, 10]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FieldRelation.php',
            ],
            1 => [
                [
                    'filter' => new Criterion\FieldRelation(
                        'image',
                        Criterion\Operator::IN,
                        [4, 49]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FieldRelationAll.php',
            ],
            2 => [
                [
                    'filter' => new Criterion\FieldRelation(
                        'image',
                        Criterion\Operator::IN,
                        [4]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FieldRelation.php',
            ],
            3 => [
                [
                    'filter' => new Criterion\FieldRelation(
                        'image',
                        Criterion\Operator::CONTAINS,
                        [1, 4, 10]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'MatchNone.php',
            ],
            4 => [
                [
                    'filter' => new Criterion\FieldRelation(
                        'image',
                        Criterion\Operator::CONTAINS,
                        [4, 49]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'MatchNone.php',
            ],
            5 => [
                [
                    'filter' => new Criterion\FieldRelation(
                        'image',
                        Criterion\Operator::CONTAINS,
                        [4]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ],
                $fixtureDir . 'FieldRelation.php',
            ],
        ];
    }

    /**
     * Purely for creating relation data needed for testFindRelationFieldContentInfoFiltered()
     * and testFindRelationFieldLocationsFiltered().
     */
    public function testRelationContentCreation()
    {
        $repository = $this->getRepository();
        $galleryType = $repository->getContentTypeService()->loadContentTypeByIdentifier('gallery');
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $locationCreateStruct = $locationService->newLocationCreateStruct(2); // Home

        $createStruct = $contentService->newContentCreateStruct($galleryType, 'eng-GB');
        $createStruct->setField('name', 'Image gallery');
        $createStruct->setField('image', 49); // Images folder
        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($galleryType, 'eng-GB');
        $createStruct->setField('name', 'User gallery');
        $createStruct->setField('image', 4); // User folder
        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);
    }

    /**
     * Test for FieldRelation using findContentInfo() method.
     *
     * @dataProvider getRelationFieldFilterSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContentInfo()
     *
     * @depends testRelationContentCreation
     */
    public function testFindRelationFieldContentInfoFiltered($queryData, $fixture)
    {
        $this->getRepository(false); // To make sure repo is setup w/o removing data from getRelationFieldFilterContentSearches
        $query = new Query($queryData);
        $this->assertQueryFixture($query, $fixture, null, true, true, false);
    }

    /**
     * Test for FieldRelation using findLocations() method.
     *
     * @dataProvider getRelationFieldFilterSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @depends testRelationContentCreation
     */
    public function testFindRelationFieldLocationsFiltered($queryData, $fixture)
    {
        $this->getRepository(false); // To make sure repo is setup w/o removing data from getRelationFieldFilterContentSearches
        $query = new LocationQuery($queryData);
        $this->assertQueryFixture($query, $fixture, null, true, false, false);
    }

    public function testFindSingle()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $content = $searchService->findSingle(
            new Criterion\ContentId(
                [4]
            )
        );

        self::assertEquals(
            4,
            $content->id
        );
    }

    public function testFindNoPerformCount()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $query = new Query();
        $query->performCount = false;
        $query->query = new Criterion\ContentTypeId(
            [4]
        );

        $searchHit = $searchService->findContent($query);

        if ($this->isRunningOnLegacySetup()) {
            self::assertNull(
                $searchHit->totalCount
            );
        } else {
            self::assertEquals(
                2,
                $searchHit->totalCount
            );
        }
    }

    public function testFindNoPerformCountException()
    {
        $this->expectException(\RuntimeException::class);

        if (!$this->isRunningOnLegacySetup()) {
            self::markTestSkipped('Only applicable to Legacy/DB based search');
        }

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $query = new Query();
        $query->performCount = false;
        $query->limit = 0;
        $query->query = new Criterion\ContentTypeId(
            [4]
        );

        $searchService->findContent($query);
    }

    public function testFindLocationsNoPerformCount()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $query = new LocationQuery();
        $query->performCount = false;
        $query->query = new Criterion\ContentTypeId(
            [4]
        );

        $searchHit = $searchService->findLocations($query);

        if ($this->isRunningOnLegacySetup()) {
            self::assertNull(
                $searchHit->totalCount
            );
        } else {
            self::assertEquals(
                2,
                $searchHit->totalCount
            );
        }
    }

    public function testFindLocationsNoPerformCountException()
    {
        $this->expectException(\RuntimeException::class);

        if (!$this->isRunningOnLegacySetup()) {
            self::markTestSkipped('Only applicable to Legacy/DB based search');
        }

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $query = new LocationQuery();
        $query->performCount = false;
        $query->limit = 0;
        $query->query = new Criterion\ContentTypeId(
            [4]
        );

        $searchService->findLocations($query);
    }

    /**
     * Create movie Content with subtitle field set to null.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content[]
     */
    protected function createMovieContent()
    {
        $movies = [];

        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('movie');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->remoteId = 'movie-123';
        $createStruct->names = ['eng-GB' => 'Movie'];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new \DateTime();

        $fieldTitle = $contentTypeService->newFieldDefinitionCreateStruct('title', 'ibexa_string');
        $fieldTitle->names = ['eng-GB' => 'Title'];
        $fieldTitle->fieldGroup = 'main';
        $fieldTitle->position = 1;
        $fieldTitle->isTranslatable = false;
        $fieldTitle->isSearchable = true;
        $fieldTitle->isRequired = true;
        $createStruct->addFieldDefinition($fieldTitle);

        $fieldSubtitle = $contentTypeService->newFieldDefinitionCreateStruct('subtitle', 'ibexa_string');
        $fieldSubtitle->names = ['eng-GB' => 'Subtitle'];
        $fieldSubtitle->fieldGroup = 'main';
        $fieldSubtitle->position = 2;
        $fieldSubtitle->isTranslatable = false;
        $fieldSubtitle->isSearchable = true;
        $fieldSubtitle->isRequired = false;
        $createStruct->addFieldDefinition($fieldSubtitle);

        $contentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentTypeGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        $createStructRambo = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStructRambo->remoteId = 'movie-456';
        $createStructRambo->alwaysAvailable = false;
        $createStructRambo->setField('title', 'Rambo');

        $ramboDraft = $contentService->createContent($createStructRambo);
        $movies[] = $contentService->publishVersion($ramboDraft->getVersionInfo());
        $this->refreshSearch($repository);
        $createStructRobocop = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStructRobocop->remoteId = 'movie-789';
        $createStructRobocop->alwaysAvailable = false;
        $createStructRobocop->setField('title', 'Robocop');
        $createStructRobocop->setField('subtitle', '');

        $robocopDraft = $contentService->createContent($createStructRobocop);
        $movies[] = $contentService->publishVersion($robocopDraft->getVersionInfo());
        $this->refreshSearch($repository);
        $createStructLastHope = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStructLastHope->remoteId = 'movie-101112';
        $createStructLastHope->alwaysAvailable = false;
        $createStructLastHope->setField('title', 'Star Wars');
        $createStructLastHope->setField('subtitle', 'Last Hope');

        $lastHopeDraft = $contentService->createContent($createStructLastHope);
        $movies[] = $contentService->publishVersion($lastHopeDraft->getVersionInfo());

        $this->refreshSearch($repository);

        return $movies;
    }

    /**
     * Create test Content with ibexa_country field having multiple countries selected.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    protected function createMultipleCountriesContent()
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('countries-multiple');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->remoteId = 'countries-multiple-123';
        $createStruct->names = ['eng-GB' => 'Multiple countries'];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new \DateTime();

        $fieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('countries', 'ibexa_country');
        $fieldCreate->names = ['eng-GB' => 'Countries'];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = 1;
        $fieldCreate->isTranslatable = false;
        $fieldCreate->isSearchable = true;
        $fieldCreate->fieldSettings = ['isMultiple' => true];

        $createStruct->addFieldDefinition($fieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->remoteId = 'countries-multiple-456';
        $createStruct->alwaysAvailable = false;
        $createStruct->setField(
            'countries',
            ['BE', 'DE', 'FR', 'HR', 'NO', 'PT', 'RU']
        );

        $draft = $contentService->createContent($createStruct);
        $content = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        return $content;
    }

    /**
     * Test for the findContent() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content[]
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testFieldIsEmpty()
    {
        $testContents = $this->createMovieContent();

        $query = new Query(
            [
                'query' => new Criterion\IsFieldEmpty('subtitle'),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query, ['eng-GB']);

        self::assertEquals(2, $result->totalCount);

        self::assertEquals(
            $testContents[0]->id,
            $result->searchHits[0]->valueObject->id
        );
        self::assertEquals(
            $testContents[1]->id,
            $result->searchHits[1]->valueObject->id
        );

        return $testContents;
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testFieldIsNotEmpty()
    {
        $testContents = $this->createMovieContent();

        $query = new Query(
            [
                'query' => new Criterion\IsFieldEmpty(
                    'subtitle',
                    false
                ),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query, ['eng-GB']);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $testContents[2]->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testFieldCollectionContains()
    {
        $testContent = $this->createMultipleCountriesContent();

        $query = new Query(
            [
                'query' => new Criterion\Field(
                    'countries',
                    Criterion\Operator::CONTAINS,
                    'Belgium'
                ),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $testContent->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @depends testFieldCollectionContains
     */
    public function testFieldCollectionContainsNoMatch()
    {
        $this->createMultipleCountriesContent();
        $query = new Query(
            [
                'query' => new Criterion\Field(
                    'countries',
                    Criterion\Operator::CONTAINS,
                    'Netherlands Antilles'
                ),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(0, $result->totalCount);
    }

    public function testInvalidFieldIdentifierRange()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'$criterion->target\' is invalid: No searchable Fields found for the provided Criterion target \'some_hopefully_unknown_field\'');

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findContent(
            new Query(
                [
                    'filter' => new Criterion\Field(
                        'some_hopefully_unknown_field',
                        Criterion\Operator::BETWEEN,
                        [10, 1000]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ]
            )
        );
    }

    public function testInvalidFieldIdentifierIn()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'$criterion->target\' is invalid: No searchable Fields found for the provided Criterion target \'some_hopefully_unknown_field\'');

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findContent(
            new Query(
                [
                    'filter' => new Criterion\Field(
                        'some_hopefully_unknown_field',
                        Criterion\Operator::EQ,
                        1000
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ]
            )
        );
    }

    public function testFindContentWithNonSearchableField()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'$criterion->target\' is invalid: No searchable Fields found for the provided Criterion target \'tag_cloud_url\'');

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findContent(
            new Query(
                [
                    'filter' => new Criterion\Field(
                        'tag_cloud_url',
                        Criterion\Operator::EQ,
                        'http://nimbus.com'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ]
            )
        );
    }

    public function testSortFieldWithNonSearchableField()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'$sortClause->targetData\' is invalid: No searchable Fields found for the provided Sort Clause target \'title\' on \'template_look\'');

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findContent(
            new Query(
                [
                    'sortClauses' => [new SortClause\Field('template_look', 'title')],
                ]
            )
        );
    }

    public function testSortMapLocationDistanceWithNonSearchableField()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'$sortClause->targetData\' is invalid: No searchable Fields found for the provided Sort Clause target \'title\' on \'template_look\'');

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findContent(
            new Query(
                [
                    'sortClauses' => [
                        new SortClause\MapLocationDistance(
                            'template_look',
                            'title',
                            1,
                            2
                        ),
                    ],
                ]
            )
        );
    }

    public function testFindSingleFailMultiple()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findSingle(
            new Criterion\ContentId(
                [4, 10]
            )
        );
    }

    public function testFindSingleWithNonSearchableField()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findSingle(
            new Criterion\Field(
                'tag_cloud_url',
                Criterion\Operator::EQ,
                'http://nimbus.com'
            )
        );
    }

    public function getSortedContentSearches()
    {
        $fixtureDir = $this->getFixtureDir();

        yield [
            [
                'filter' => new Criterion\SectionId([2]),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ],
            $fixtureDir . 'SortNone.php',
            // Result having the same sort level should be sorted between them to be system independent
            static function (&$data): void {
                usort(
                    $data->searchHits,
                    static function (SearchHit $a, SearchHit $b): int {
                        return $a->valueObject['id'] <=> $b->valueObject['id'];
                    }
                );
            },
        ];

        yield [
            [
                'filter' => new Criterion\SectionId([2]),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    new SortClause\DatePublished(),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortDatePublished.php',
        ];

        yield [
            [
                'filter' => new Criterion\SectionId([2]),
                'offset' => 0,
                'limit' => 50,
                'sortClauses' => [
                    new SortClause\DateModified(),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortDateModified.php',
        ];

        yield [
            [
                'filter' => new Criterion\SectionId([4, 2, 6, 3]),
                'offset' => 0,
                'limit' => 50,
                'sortClauses' => [
                    new SortClause\SectionIdentifier(),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortSectionIdentifier.php',
        ];

        yield [
            [
                'filter' => new Criterion\SectionId([4, 2, 6, 3]),
                'offset' => 0,
                'limit' => 50,
                'sortClauses' => [
                    new SortClause\SectionName(),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortSectionName.php',
        ];

        yield [
            [
                'filter' => new Criterion\SectionId([2, 3]),
                'offset' => 0,
                'limit' => 50,
                'sortClauses' => [
                    new SortClause\ContentName(),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortContentName.php',
        ];

        yield [
            [
                'filter' => new Criterion\ContentTypeId(1),
                'offset' => 0,
                'limit' => 50,
                'sortClauses' => [
                    new SortClause\Field('folder', 'name', Query::SORT_ASC),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortFolderName.php',
        ];

        yield [
            [
                'filter' => new Criterion\ContentTypeId([1, 3]),
                'offset' => 0,
                'limit' => 50,
                'sortClauses' => [
                    new SortClause\Field('folder', 'name', Query::SORT_ASC),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortFieldMultipleTypes.php',
        ];

        yield [
            [
                'filter' => new Criterion\ContentTypeId([1, 3]),
                'offset' => 0,
                'limit' => 50,
                'sortClauses' => [
                    new SortClause\Field('folder', 'name', Query::SORT_DESC),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortFieldMultipleTypesReverse.php',
        ];

        yield [
            [
                'filter' => new Criterion\ContentTypeId([1, 3]),
                'offset' => 3,
                'limit' => 5,
                'sortClauses' => [
                    new SortClause\Field('folder', 'name', Query::SORT_ASC),
                    new SortClause\Field('user', 'first_name', Query::SORT_ASC),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortFieldMultipleTypesSlice.php',
        ];

        yield [
            [
                'filter' => new Criterion\ContentTypeId([1, 3]),
                'offset' => 3,
                'limit' => 5,
                'sortClauses' => [
                    new SortClause\Field('folder', 'name', Query::SORT_DESC),
                    new SortClause\Field('user', 'first_name', Query::SORT_ASC),
                    new SortClause\ContentId(),
                ],
            ],
            $fixtureDir . 'SortFieldMultipleTypesSliceReverse.php',
        ];

        if (!$this->isLegacySearchEngineSetup()) {
            yield [
                [
                    'filter' => new Criterion\ContentTypeId(1),
                    'offset' => 0,
                    'limit' => 50,
                    'sortClauses' => [
                        new SortClause\CustomField('folder_name_value_s', Query::SORT_ASC),
                        new SortClause\ContentId(),
                    ],
                ],
                $fixtureDir . 'SortFolderName.php',
            ];
        }
    }

    public function getSortedLocationSearches()
    {
        $fixtureDir = $this->getFixtureDir();

        return [
            [
                [
                    'filter' => new Criterion\SectionId([2]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [new SortClause\Location\Path(Query::SORT_DESC)],
                ],
                $fixtureDir . 'SortPathString.php',
            ],
            [
                [
                    'filter' => new Criterion\SectionId([2]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [new SortClause\Location\Depth(Query::SORT_ASC)],
                ],
                $fixtureDir . 'SortLocationDepth.php',
                // Result having the same sort level should be sorted between them to be system independent
                static function (&$data) {
                    // Result with ids:
                    //     4 has depth = 1
                    //     11, 12, 13, 42, 59 have depth = 2
                    //     10, 14 have depth = 3
                    $map = [
                        4 => 0,
                        11 => 1,
                        12 => 2,
                        13 => 3,
                        42 => 4,
                        59 => 5,
                        10 => 6,
                        14 => 7,
                    ];
                    usort(
                        $data->searchHits,
                        static function ($a, $b) use ($map): int {
                            return ($map[$a->valueObject['id']] < $map[$b->valueObject['id']]) ? -1 : 1;
                        }
                    );
                },
            ],
            [
                [
                    'filter' => new Criterion\SectionId([3]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [
                        new SortClause\Location\Path(Query::SORT_DESC),
                        new SortClause\ContentName(Query::SORT_ASC),
                    ],
                ],
                $fixtureDir . 'SortMultiple.php',
            ],
            [
                [
                    'filter' => new Criterion\SectionId([2]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [
                        new SortClause\Location\Priority(Query::SORT_DESC),
                        new SortClause\ContentId(),
                    ],
                ],
                $fixtureDir . 'SortDesc.php',
            ],
        ];
    }

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
        $createStruct->creationDate = new \DateTime();

        $translatableFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('integer', 'ibexa_integer');
        $translatableFieldCreate->names = ['eng-GB' => 'Simple translatable integer field'];
        $translatableFieldCreate->fieldGroup = 'main';
        $translatableFieldCreate->position = 1;
        $translatableFieldCreate->isTranslatable = true;
        $translatableFieldCreate->isSearchable = true;

        $createStruct->addFieldDefinition($translatableFieldCreate);

        $nonTranslatableFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('integer2', 'ibexa_integer');
        $nonTranslatableFieldCreate->names = ['eng-GB' => 'Simple non-translatable integer field'];
        $nonTranslatableFieldCreate->fieldGroup = 'main';
        $nonTranslatableFieldCreate->position = 2;
        $nonTranslatableFieldCreate->isTranslatable = false;
        $nonTranslatableFieldCreate->isSearchable = true;

        $createStruct->addFieldDefinition($nonTranslatableFieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        return $contentType;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType $contentType
     * @param int $fieldValue11 Value for translatable field in first language
     * @param int $fieldValue12 Value for translatable field in second language
     * @param int $fieldValue2 Value for non translatable field
     * @param string $mainLanguageCode
     * @param bool $alwaysAvailable
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    protected function createMultilingualContent(
        $contentType,
        $fieldValue11 = null,
        $fieldValue12 = null,
        $fieldValue2 = null,
        $mainLanguageCode = 'eng-GB',
        $alwaysAvailable = false
    ) {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = $alwaysAvailable;
        $createStruct->mainLanguageCode = $mainLanguageCode;
        if ($fieldValue11) {
            $createStruct->setField('integer', $fieldValue11, 'eng-GB');
        }
        if ($fieldValue12) {
            $createStruct->setField('integer', $fieldValue12, 'ger-DE');
        }
        $createStruct->setField('integer2', $fieldValue2, $mainLanguageCode);

        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);
        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $content = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        return $content;
    }

    public function providerForTestMultilingualFieldSort()
    {
        return [
            0 => [
                [
                    1 => [1, 2, 1],
                    2 => [2, 1, 2],
                    3 => [2, 1, 3],
                    4 => [1, 2, 4],
                ],
                [
                    'languages' => [
                        'eng-GB',
                        'ger-DE',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_ASC),
                    new SortClause\Field('test-type', 'integer2', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 4, 1, 2, 4
                 * Content 1, 1, 2, 1
                 * Content 3, 2, 1, 3
                 * Content 2, 2, 1, 2
                 */
                [4, 1, 3, 2],
            ],
            1 => [
                [
                    1 => [1, 2, 1],
                    2 => [2, 1, 2],
                    3 => [2, 1, 3],
                    4 => [1, 2, 4],
                ],
                [
                    'languages' => [
                        'ger-DE',
                        'eng-GB',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_ASC),
                    new SortClause\Field('test-type', 'integer2', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 3, 2, 1, 3
                 * Content 2, 2, 1, 2
                 * Content 4, 1, 2, 4
                 * Content 1, 1, 2, 1
                 */
                [3, 2, 4, 1],
            ],
            2 => [
                [
                    1 => [null, 2, null, 'ger-DE'],
                    2 => [3, null, null, 'eng-GB'],
                    3 => [4, null, null, 'eng-GB'],
                    4 => [null, 1, null, 'ger-DE'],
                ],
                [
                    'languages' => [
                        'eng-GB',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 3, 4, -
                 * Content 2, 3, -
                 */
                [3, 2],
            ],
            3 => [
                [
                    1 => [null, 2, null, 'ger-DE'],
                    2 => [3, null, null, 'eng-GB'],
                    3 => [4, null, null, 'eng-GB'],
                    4 => [null, 1, null, 'ger-DE'],
                ],
                [
                    'languages' => [
                        'ger-DE',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 1, -, 2
                 * Content 4, -, 1
                 */
                [1, 4],
            ],
            4 => [
                [
                    1 => [null, 2, null, 'ger-DE'],
                    2 => [3, null, null, 'eng-GB'],
                    3 => [4, null, null, 'eng-GB'],
                    4 => [null, 1, null, 'ger-DE'],
                ],
                [
                    'languages' => [
                        'eng-GB',
                        'ger-DE',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 3, 4, -
                 * Content 2, 3, -
                 * Content 1, -, 2
                 * Content 4, -, 1
                 */
                [3, 2, 1, 4],
            ],
            5 => [
                [
                    1 => [null, 2, null, 'ger-DE'],
                    2 => [3, null, null, 'eng-GB'],
                    3 => [4, null, null, 'eng-GB'],
                    4 => [null, 1, null, 'ger-DE'],
                ],
                [
                    'languages' => [
                        'ger-DE',
                        'eng-GB',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 3, 4, -
                 * Content 2, 3, -
                 * Content 1, -, 2
                 * Content 4, -, 1
                 */
                [3, 2, 1, 4],
            ],
            6 => [
                [
                    1 => [null, 2, null, 'ger-DE'],
                    2 => [3, 4, null, 'eng-GB'],
                    3 => [4, 3, null, 'eng-GB'],
                    4 => [null, 1, null, 'ger-DE'],
                ],
                [
                    'languages' => [
                        'eng-GB',
                        'ger-DE',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 3, 4, 3
                 * Content 2, 3, 4
                 * Content 1, -, 2
                 * Content 4, -, 1
                 */
                [3, 2, 1, 4],
            ],
            7 => [
                [
                    1 => [null, 2, null, 'ger-DE'],
                    2 => [3, 4, null, 'eng-GB'],
                    3 => [4, 3, null, 'eng-GB'],
                    4 => [null, 1, null, 'ger-DE'],
                ],
                [
                    'languages' => [
                        'ger-DE',
                        'eng-GB',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 2, 3, 4
                 * Content 3, 4, 3
                 * Content 1, -, 2
                 * Content 4, -, 1
                 */
                [2, 3, 1, 4],
            ],
            8 => [
                [
                    1 => [null, 1, null, 'ger-DE', true],
                    2 => [4, null, null, 'eng-GB', true],
                    3 => [3, null, null, 'eng-GB', false],
                    4 => [null, 2, null, 'ger-DE', false],
                ],
                [
                    'languages' => [
                        'eng-GB',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_ASC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 1, -, 1
                 * Content 3, 3, -
                 * Content 2, 4, -
                 */
                [1, 3, 2],
            ],
            9 => [
                [
                    1 => [null, 1, null, 'ger-DE', true],
                    2 => [4, null, null, 'eng-GB', true],
                    3 => [3, null, null, 'eng-GB', false],
                    4 => [null, 2, null, 'ger-DE', false],
                ],
                [
                    'languages' => [
                        'ger-DE',
                    ],
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 2, 4, -
                 * Content 4, -, 2
                 * Content 1, -, 1
                 */
                [2, 4, 1],
            ],
            10 => [
                [
                    1 => [null, 1, null, 'ger-DE', true],
                    2 => [4, null, null, 'eng-GB', true],
                    3 => [3, null, null, 'eng-GB', false],
                    4 => [null, 2, null, 'ger-DE', false],
                ],
                [
                    'languages' => [
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_ASC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 3, 3, -
                 * Content 2, 4, -
                 */
                [3, 2],
            ],
            11 => [
                [
                    1 => [null, 1, null, 'ger-DE', true],
                    2 => [4, null, null, 'eng-GB', true],
                    3 => [3, null, null, 'eng-GB', false],
                    4 => [null, 2, null, 'ger-DE', false],
                ],
                [
                    'languages' => [
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                [
                    new SortClause\Field('test-type', 'integer', Query::SORT_DESC),
                ],
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 4, -, 2
                 * Content 1, -, 1
                 */
                [4, 1],
            ],
        ];
    }

    /**
     * Test for the findContent() method.
     *
     * @group rrr
     *
     * @dataProvider providerForTestMultilingualFieldSort
     *
     * @param array $contentDataList
     * @param array $languageSettings
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $sortClauses
     * @param array $expected
     */
    public function testMultilingualFieldSortContent(
        array $contentDataList,
        $languageSettings,
        array $sortClauses,
        $expected
    ) {
        $this->assertMultilingualFieldSort(
            $contentDataList,
            $languageSettings,
            $sortClauses,
            $expected
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @group rrr
     *
     * @dataProvider providerForTestMultilingualFieldSort
     *
     * @param array $contentDataList
     * @param array $languageSettings
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $sortClauses
     * @param array $expected
     */
    public function testMultilingualFieldSortLocation(
        array $contentDataList,
        $languageSettings,
        array $sortClauses,
        $expected
    ) {
        $this->assertMultilingualFieldSort(
            $contentDataList,
            $languageSettings,
            $sortClauses,
            $expected,
            false
        );
    }

    /**
     * @param array $contentDataList
     * @param array $languageSettings
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $sortClauses
     * @param array $expected
     * @param bool $contentSearch
     */
    protected function assertMultilingualFieldSort(
        array $contentDataList,
        $languageSettings,
        array $sortClauses,
        $expected,
        $contentSearch = true
    ) {
        $contentType = $this->createTestContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentTypeService->createContentTypeDraft($contentType);

        $defaults = [null, null, null, 'eng-GB', false];
        $contentIdList = [];
        foreach ($contentDataList as $key => $contentData) {
            $contentData = $contentData + $defaults;
            list(
                $fieldValue11,
                $fieldValue12,
                $fieldValue2,
                $mainLanguageCode,
                $alwaysAvailable
            ) = $contentData;

            $contentIdList[$key] = $this->createMultilingualContent(
                $contentType,
                $fieldValue11,
                $fieldValue12,
                $fieldValue2,
                $mainLanguageCode,
                $alwaysAvailable
            )->id;
        }

        // "article" type Content is not matched, this ensures that non-matched
        // field does not affect sort
        $dummySortClause = new SortClause\Field('article', 'title', Query::SORT_ASC);
        array_unshift($sortClauses, $dummySortClause);
        $sortClauses[] = $dummySortClause;

        $searchService = $repository->getSearchService();
        if ($contentSearch) {
            $query = new Query(
                [
                    'query' => new Criterion\ContentTypeId($contentType->id),
                    'sortClauses' => $sortClauses,
                ]
            );
            $result = $searchService->findContent($query, $languageSettings);
        } else {
            $query = new LocationQuery(
                [
                    'query' => new Criterion\ContentTypeId($contentType->id),
                    'sortClauses' => $sortClauses,
                ]
            );
            $result = $searchService->findLocations($query, $languageSettings);
        }

        self::assertEquals(count($expected), $result->totalCount);

        $expectedIdList = [];
        foreach ($expected as $contentNumber) {
            $expectedIdList[] = $contentIdList[$contentNumber];
        }

        self::assertEquals($expectedIdList, $this->mapResultContentIds($result));
    }

    public function providerForTestMultilingualFieldFilter()
    {
        return [
            0 => [
                $fixture = [
                    1 => [null, 1, null, 'ger-DE', true],
                    2 => [4, null, null, 'eng-GB', true],
                    3 => [3, null, null, 'eng-GB', false],
                    4 => [null, 2, null, 'ger-DE', false],
                    5 => [5, null, null, 'eng-GB', true],
                ],
                $languageSettings = [
                    'languages' => [
                        'ger-DE',
                    ],
                ],
                new Criterion\Field('integer', Criterion\Operator::LT, 5),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 2, 4, -
                 * Content 4, -, 2
                 * Content 1, -, 1
                 */
                [2, 4, 1],
            ],
            1 => [
                $fixture,
                [
                    'languages' => [
                        'ger-DE',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                new Criterion\Field('integer', Criterion\Operator::LT, 2),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 1, -, 1
                 */
                [1],
            ],
            2 => [
                $fixture,
                [
                    'languages' => [
                        'eng-GB',
                    ],
                ],
                new Criterion\Field('integer', Criterion\Operator::LTE, 4),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 5, 5, -
                 * Content 2, 4, -
                 * Content 3, 3, -
                 * Content 1, -, 1
                 */
                [2, 3, 1],
            ],
            3 => [
                $fixture,
                [
                    'languages' => [
                        'eng-GB',
                    ],
                    'useAlwaysAvailable' => false,
                ],
                new Criterion\Field('integer', Criterion\Operator::LTE, 4),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 2, 4, -
                 * Content 3, 3, -
                 */
                [2, 3],
            ],
            4 => [
                $fixture,
                $languageSettings,
                new Criterion\Field('integer', Criterion\Operator::LTE, 4),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 2, 4, -
                 * Content 4, -, 2
                 * Content 1, -, 1
                 */
                [2, 4, 1],
            ],
            5 => [
                $fixture,
                $languageSettings,
                new Criterion\Field('integer', Criterion\Operator::GT, 1),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 5, 5, -
                 * Content 2, 4, -
                 * Content 4, -, 2
                 */
                [5, 2, 4],
            ],
            6 => [
                $fixture,
                $languageSettings,
                new Criterion\Field('integer', Criterion\Operator::GTE, 2),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 5, 5, -
                 * Content 2, 4, -
                 * Content 4, -, 2
                 */
                [5, 2, 4],
            ],
            7 => [
                $fixture,
                $languageSettings,
                new Criterion\Field('integer', Criterion\Operator::BETWEEN, [2, 4]),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 2, 4, -
                 * Content 4, -, 2
                 */
                [2, 4],
            ],
            8 => [
                $fixture,
                $languageSettings,
                new Criterion\Field('integer', Criterion\Operator::BETWEEN, [4, 2]),
                [],
            ],
            9 => [
                $fixture,
                $languageSettings,
                new Criterion\Field('integer', Criterion\Operator::EQ, 4),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 4, -, 2
                 */
                [2],
            ],
            10 => [
                $fixture,
                $languageSettings,
                new Criterion\Field('integer', Criterion\Operator::EQ, 2),
                /**
                 * Expected order, Value eng-GB, Value ger-DE.
                 *
                 * Content 2, 4, -
                 */
                [4],
            ],
        ];
    }

    /**
     * Test for the findContent() method.
     *
     * @group ttt
     *
     * @dataProvider providerForTestMultilingualFieldFilter
     *
     * @param array $contentDataList
     * @param array $languageSettings
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param array $expected
     */
    public function testMultilingualFieldFilterContent(
        array $contentDataList,
        $languageSettings,
        Criterion $criterion,
        $expected
    ) {
        $this->assertMultilingualFieldFilter(
            $contentDataList,
            $languageSettings,
            $criterion,
            $expected
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @group ttt
     *
     * @dataProvider providerForTestMultilingualFieldFilter
     *
     * @param array $contentDataList
     * @param array $languageSettings
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param array $expected
     */
    public function testMultilingualFieldFilterLocation(
        array $contentDataList,
        $languageSettings,
        Criterion $criterion,
        $expected
    ) {
        $this->assertMultilingualFieldFilter(
            $contentDataList,
            $languageSettings,
            $criterion,
            $expected,
            false
        );
    }

    /**
     * @param array $contentDataList
     * @param array $languageSettings
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param array $expected
     * @param bool $contentSearch
     */
    protected function assertMultilingualFieldFilter(
        array $contentDataList,
        $languageSettings,
        Criterion $criterion,
        $expected,
        $contentSearch = true
    ) {
        $contentType = $this->createTestContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentTypeService->createContentTypeDraft($contentType);

        $defaults = [null, null, null, 'eng-GB', false];
        $contentIdList = [];
        foreach ($contentDataList as $key => $contentData) {
            $contentData = $contentData + $defaults;
            list(
                $fieldValue11,
                $fieldValue12,
                $fieldValue2,
                $mainLanguageCode,
                $alwaysAvailable
            ) = $contentData;

            $contentIdList[$key] = $this->createMultilingualContent(
                $contentType,
                $fieldValue11,
                $fieldValue12,
                $fieldValue2,
                $mainLanguageCode,
                $alwaysAvailable
            )->id;
        }

        $sortClause = new SortClause\Field('test-type', 'integer', Query::SORT_DESC);
        $searchService = $repository->getSearchService();
        if ($contentSearch) {
            $query = new Query(
                [
                    'query' => new Criterion\LogicalAnd(
                        [
                            new Criterion\ContentTypeId($contentType->id),
                            $criterion,
                        ]
                    ),
                    'sortClauses' => [$sortClause],
                ]
            );
            $result = $searchService->findContent($query, $languageSettings);
        } else {
            $query = new LocationQuery(
                [
                    'query' => new Criterion\LogicalAnd(
                        [
                            new Criterion\ContentTypeId($contentType->id),
                            $criterion,
                        ]
                    ),
                    'sortClauses' => [$sortClause],
                ]
            );
            $result = $searchService->findLocations($query, $languageSettings);
        }

        self::assertEquals(count($expected), $result->totalCount);

        $expectedIdList = [];
        foreach ($expected as $contentNumber) {
            $expectedIdList[] = $contentIdList[$contentNumber];
        }

        self::assertEquals($expectedIdList, $this->mapResultContentIds($result));
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult $result
     *
     * @return array
     */
    protected function mapResultContentIds(SearchResult $result): array
    {
        return array_map(
            static function (SearchHit $searchHit) {
                if ($searchHit->valueObject instanceof Location) {
                    return $searchHit->valueObject->contentInfo->id;
                }

                return $searchHit->valueObject->id;
            },
            $result->searchHits
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @dataProvider getSortedContentSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testFindAndSortContent($queryData, $fixture, $closure = null)
    {
        $query = new Query($queryData);
        $this->assertQueryFixture($query, $fixture, $closure);
    }

    /**
     * Test for the findContentInfo() method.
     *
     * @dataProvider getSortedContentSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContentInfo()
     */
    public function testFindAndSortContentInfo($queryData, $fixture, $closure = null)
    {
        $query = new Query($queryData);
        $this->assertQueryFixture($query, $fixture, $this->getContentInfoFixtureClosure($closure), true);
    }

    /**
     * Test for the findLocations() method.
     *
     * @dataProvider getSortedContentSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFindAndSortContentLocations($queryData, $fixture, $closure = null)
    {
        $query = new LocationQuery($queryData);
        $this->assertQueryFixture($query, $fixture, $closure);
    }

    /**
     * Test for the findLocations() method.
     *
     * @dataProvider getSortedLocationSearches
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFindAndSortLocations($queryData, $fixture, $closure = null)
    {
        $query = new LocationQuery($queryData);
        $this->assertQueryFixture($query, $fixture, $closure);
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testQueryCustomField()
    {
        $query = new Query(
            [
                'query' => new Criterion\CustomField(
                    'custom_field',
                    Criterion\Operator::EQ,
                    'AdMiNiStRaToR'
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [new SortClause\ContentId()],
            ]
        );
        $this->assertQueryFixture(
            $query,
            $this->getFixtureDir() . '/QueryCustomField.php'
        );
    }

    /**
     * Test for the findContent() method.
     *
     * This tests explicitely queries the first_name while user is contained in
     * the last_name of admin and anonymous. This is done to show the custom
     * copy field working.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testQueryModifiedField()
    {
        // Check using get_class since the others extend SetupFactory\Legacy
        if ($this->getSetupFactory() instanceof Legacy) {
            self::markTestIncomplete(
                'Custom fields not supported by LegacySE ' .
                '(@todo: Legacy should fallback to just querying normal field so this should be tested here)'
            );
        }

        $query = new Query(
            [
                'query' => new Criterion\Field(
                    'first_name',
                    Criterion\Operator::EQ,
                    'User'
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [new SortClause\ContentId()],
            ]
        );
        self::assertInstanceOf(Criterion::class, $query->query);
        $query->query->setCustomField('user', 'first_name', 'custom_field');

        $this->assertQueryFixture(
            $query,
            $this->getFixtureDir() . '/QueryModifiedField.php'
        );
    }

    /**
     * Test for the findContent() method.
     *
     * This tests first explicitly creates sort clause on the 'short_name' which is empty
     * for all Content instances of 'folder' ContentType. Custom sort field is then set
     * to the index storage name of folder's 'name' field, in order to show the custom
     * sort field working.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testSortModifiedField()
    {
        // Check using get_class since the others extend SetupFactory\Legacy
        if ($this->getSetupFactory() instanceof Legacy) {
            self::markTestIncomplete(
                'Custom field sort not supported by LegacySE ' .
                '(@todo: Legacy should fallback to just querying normal field so this should be tested here)'
            );
        }

        $sortClause = new SortClause\Field('folder', 'short_name', Query::SORT_ASC);
        $sortClause->setCustomField('folder', 'short_name', 'folder_name_value_s');

        $query = new Query(
            [
                'filter' => new Criterion\ContentTypeId(1),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    $sortClause,
                    new SortClause\ContentId(),
                ],
            ]
        );

        $this->assertQueryFixture(
            $query,
            $this->getFixtureDir() . '/SortFolderName.php'
        );
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType
     */
    protected function createTestPlaceContentType()
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('testtype');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->names = ['eng-GB' => 'Test type'];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new \DateTime();

        $translatableFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('maplocation', 'ibexa_gmap_location');
        $translatableFieldCreate->names = ['eng-GB' => 'Map location field'];
        $translatableFieldCreate->fieldGroup = 'main';
        $translatableFieldCreate->position = 1;
        $translatableFieldCreate->isTranslatable = false;
        $translatableFieldCreate->isSearchable = true;

        $createStruct->addFieldDefinition($translatableFieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        return $contentType;
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceLessThanOrEqual()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $query = new Query(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::LTE,
                            240,
                            43.756825,
                            15.775074
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $wildBoars->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceGreaterThanOrEqual()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $query = new Query(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::GTE,
                            240,
                            43.756825,
                            15.775074
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $tree->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceBetween()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.903777,
                'longitude' => 15.958788,
                'address' => 'Meadow with mushrooms',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $mushrooms = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $query = new Query(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::BETWEEN,
                            [239, 241],
                            43.756825,
                            15.775074
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $mushrooms->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * This tests the distance over the pole. The tests intentionally uses large range,
     * as the flat Earth model used in Legacy Storage Search is not precise for the use case.
     * What is tested here is that outer bounding box is correctly calculated, so that
     * location is not excluded.
     *
     * Range between 222km and 350km shows the magnitude of error between great-circle
     * (always very precise) and flat Earth (very imprecise for this use case) models.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceBetweenPolar()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 89,
                'longitude' => -164,
                'address' => 'Polar bear media tower',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $polarBear = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $query = new Query(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::BETWEEN,
                            [221, 350],
                            89,
                            16
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $polarBear->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceSortAscending()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.903777,
                'longitude' => 15.958788,
                'address' => 'Meadow with mushrooms',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $mushrooms = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $wellInVodice = [
            'latitude' => 43.756825,
            'longitude' => 15.775074,
        ];

        $query = new Query(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::GTE,
                            235,
                            $wellInVodice['latitude'],
                            $wellInVodice['longitude']
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    new SortClause\MapLocationDistance(
                        'testtype',
                        'maplocation',
                        $wellInVodice['latitude'],
                        $wellInVodice['longitude'],
                        Query::SORT_ASC
                    ),
                ],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(3, $result->totalCount);
        self::assertEquals(
            $wildBoars->id,
            $result->searchHits[0]->valueObject->id
        );
        self::assertEquals(
            $mushrooms->id,
            $result->searchHits[1]->valueObject->id
        );
        self::assertEquals(
            $tree->id,
            $result->searchHits[2]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceSortDescending()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.903777,
                'longitude' => 15.958788,
                'address' => 'Meadow with mushrooms',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $mushrooms = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $well = [
            'latitude' => 43.756825,
            'longitude' => 15.775074,
        ];

        $query = new Query(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::GTE,
                            235,
                            $well['latitude'],
                            $well['longitude']
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    new SortClause\MapLocationDistance(
                        'testtype',
                        'maplocation',
                        $well['latitude'],
                        $well['longitude'],
                        Query::SORT_DESC
                    ),
                ],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(3, $result->totalCount);
        self::assertEquals(
            $wildBoars->id,
            $result->searchHits[2]->valueObject->id
        );
        self::assertEquals(
            $mushrooms->id,
            $result->searchHits[1]->valueObject->id
        );
        self::assertEquals(
            $tree->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceWithCustomField()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $distanceCriterion = new Criterion\MapLocationDistance(
            'maplocation',
            Criterion\Operator::LTE,
            240,
            43.756825,
            15.775074
        );
        $distanceCriterion->setCustomField('testtype', 'maplocation', 'custom_geolocation_field');

        $query = new Query(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        $distanceCriterion,
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $wildBoars->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceWithCustomFieldSort()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.903777,
                'longitude' => 15.958788,
                'address' => 'Meadow with mushrooms',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct);
        $mushrooms = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $well = [
            'latitude' => 43.756825,
            'longitude' => 15.775074,
        ];

        $sortClause = new SortClause\MapLocationDistance(
            'testtype',
            'maplocation',
            $well['latitude'],
            $well['longitude'],
            Query::SORT_DESC
        );
        $sortClause->setCustomField('testtype', 'maplocation', 'custom_geolocation_field');

        $query = new Query(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::GTE,
                            235,
                            $well['latitude'],
                            $well['longitude']
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    $sortClause,
                ],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertEquals(3, $result->totalCount);
        self::assertEquals(
            $wildBoars->id,
            $result->searchHits[2]->valueObject->id
        );
        self::assertEquals(
            $mushrooms->id,
            $result->searchHits[1]->valueObject->id
        );
        self::assertEquals(
            $tree->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFindMainLocation()
    {
        $plainSiteLocationId = 56;
        $designLocationId = 58;
        $partnersContentId = 59;
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        // Add secondary Location for "Partners" user group, under "Design" page
        $locationService->createLocation(
            $contentService->loadContentInfo($partnersContentId),
            $locationService->newLocationCreateStruct($designLocationId)
        );

        $this->refreshSearch($repository);

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ParentLocationId($designLocationId),
                        new Criterion\Location\IsMainLocation(
                            Criterion\Location\IsMainLocation::MAIN
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals($plainSiteLocationId, $result->searchHits[0]->valueObject->id);
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFindNonMainLocation()
    {
        $designLocationId = 58;
        $partnersContentId = 59;
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        // Add secondary Location for "Partners" user group, under "Design" page
        $newLocation = $locationService->createLocation(
            $contentService->loadContentInfo($partnersContentId),
            $locationService->newLocationCreateStruct($designLocationId)
        );

        $this->refreshSearch($repository);

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ParentLocationId($designLocationId),
                        new Criterion\Location\IsMainLocation(
                            Criterion\Location\IsMainLocation::NOT_MAIN
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals($newLocation->id, $result->searchHits[0]->valueObject->id);
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testSortMainLocationAscending()
    {
        $plainSiteLocationId = 56;
        $designLocationId = 58;
        $partnersContentId = 59;
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        // Add secondary Location for "Partners" user group, under "Design" page
        $newLocation = $locationService->createLocation(
            $contentService->loadContentInfo($partnersContentId),
            $locationService->newLocationCreateStruct($designLocationId)
        );

        $this->refreshSearch($repository);

        $query = new LocationQuery(
            [
                'filter' => new Criterion\ParentLocationId($designLocationId),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    new SortClause\Location\IsMainLocation(
                        LocationQuery::SORT_ASC
                    ),
                ],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(2, $result->totalCount);
        self::assertEquals($newLocation->id, $result->searchHits[0]->valueObject->id);
        self::assertEquals($plainSiteLocationId, $result->searchHits[1]->valueObject->id);
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testSortMainLocationDescending()
    {
        $plainSiteLocationId = 56;
        $designLocationId = 58;
        $partnersContentId = 59;
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        // Add secondary Location for "Partners" user group, under "Design" page
        $newLocation = $locationService->createLocation(
            $contentService->loadContentInfo($partnersContentId),
            $locationService->newLocationCreateStruct($designLocationId)
        );

        $this->refreshSearch($repository);

        $query = new LocationQuery(
            [
                'filter' => new Criterion\ParentLocationId($designLocationId),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    new SortClause\Location\IsMainLocation(
                        LocationQuery::SORT_DESC
                    ),
                ],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(2, $result->totalCount);
        self::assertEquals($plainSiteLocationId, $result->searchHits[0]->valueObject->id);
        self::assertEquals($newLocation->id, $result->searchHits[1]->valueObject->id);
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testContentWithMultipleLocations()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        $forumType = $contentTypeService->loadContentTypeByIdentifier('forum');

        $createStruct = $contentService->newContentCreateStruct($forumType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->setField('name', 'An awesome duplicate forum');

        $draft = $contentService->createContent($createStruct);
        $content = $contentService->publishVersion($draft->getVersionInfo());

        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);
        $location1 = $locationService->createLocation($content->contentInfo, $locationCreateStruct);
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(5);
        $location2 = $locationService->createLocation($content->contentInfo, $locationCreateStruct);

        $this->refreshSearch($repository);

        $query = new LocationQuery(
            [
                'filter' => new Criterion\ContentId($content->id),
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(2, $result->totalCount);
        $locationIds = array_map(
            static function (SearchHit $searchHit): int {
                return $searchHit->valueObject->id;
            },
            $result->searchHits
        );
        self::assertContains($location1->id, $locationIds);
        self::assertContains($location2->id, $locationIds);
    }

    protected function createContentForTestUserMetadataGroupHorizontal()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $administratorUser = $userService->loadUser($permissionResolver->getCurrentUserReference()->getUserId());
        // ID of the "Administrators" user group in an Ibexa demo installation
        $administratorsUserGroupId = 12;
        // ID of the "Editors" user group in an Ibexa demo installation
        $editorsUserGroupId = 13;

        $administratorsUserGroup = $userService->loadUserGroup($administratorsUserGroupId);
        $editorsUserGroup = $userService->loadUserGroup($editorsUserGroupId);

        // Add additional Location for Administrators UserGroup under Editors UserGroup Location
        $locationCreateStruct = $locationService->newLocationCreateStruct(
            $editorsUserGroup->contentInfo->mainLocationId
        );
        $newAdministratorsUserGroupLocation = $locationService->createLocation(
            $administratorsUserGroup->contentInfo,
            $locationCreateStruct
        );

        // Add additional Location for administrator user under newly created UserGroup Location
        $locationCreateStruct = $locationService->newLocationCreateStruct(
            $newAdministratorsUserGroupLocation->id
        );
        $locationService->createLocation(
            $administratorUser->contentInfo,
            $locationCreateStruct
        );

        // Create a Content to be found through Editors UserGroup id.
        // This ensures data is indexed, it could also be done by updating metadata of
        // an existing Content, but listener would need to reindex Content and that should
        // be tested elsewhere (dedicated indexing integration tests, missing ATM).
        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->setField('name', 'test');

        $locationCreateStruct = $locationService->newLocationCreateStruct(2);
        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $content = $contentService->publishVersion($draft->getVersionInfo());
        $contentTypeService->createContentTypeDraft($contentType);

        $this->refreshSearch($repository);

        return $content;
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testUserMetadataGroupHorizontalFilterContent(string $queryType = null)
    {
        if ($queryType === null) {
            $queryType = 'filter';
        }

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $editorsUserGroupId = 13;

        $content = $this->createContentForTestUserMetadataGroupHorizontal();

        $criteria = [];
        $setupFactory = $this->getSetupFactory();

        // Do not limit for LSE, as it does not not require reindexing.
        // See explanation below.
        if ($setupFactory instanceof LegacySolrSetupFactory) {
            $criteria[] = new Criterion\ContentTypeIdentifier('folder');
        }

        $criteria[] = new Criterion\UserMetadata(
            Criterion\UserMetadata::GROUP,
            Criterion\Operator::EQ,
            $editorsUserGroupId
        );

        $query = new Query(
            [
                $queryType => new Criterion\LogicalAnd($criteria),
                'sortClauses' => [
                    new SortClause\ContentId(),
                ],
                'limit' => 50,
            ]
        );

        if ($setupFactory instanceof LegacySolrSetupFactory) {
            $result = $searchService->findContent($query);

            // Administrator User is owned by itself, when additional Locations are added
            // it should be reindexed and its UserGroups will updated, which means it should
            // also be found as a Content of Editors UserGroup. However we do not handle this
            // in listeners yet, and also miss SPI methods to do it without using Search (also
            // needed to decouple services), because as indexing is asynchronous Search
            // should not eat its own dog food for reindexing.
            self::assertEquals(1, $result->totalCount);

            self::assertEquals(
                $content->id,
                $result->searchHits[0]->valueObject->id
            );
        } else {
            // This is how it should eventually work for all search engines,
            // with required reindexing listeners properly implemented.

            $result = $searchService->findContent($query);

            // Assert last hit manually, as id will change because it is created in test
            // and not present it base fixture.
            $foundContent1 = array_pop($result->searchHits);
            $result->totalCount = $result->totalCount - 1;
            self::assertEquals($content->id, $foundContent1->valueObject->id);

            $this->simplifySearchResult($result);
            self::assertEqualsWithDelta(
                include $this->getFixtureDir() . '/UserMetadata.php',
                $result,
                .1, // Be quite generous regarding delay -- most important for scores
                'Search results do not match.',
            );
        }
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testUserMetadataGroupHorizontalQueryContent()
    {
        $this->testUserMetadataGroupHorizontalFilterContent('query');
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testUserMetadataGroupHorizontalFilterLocation($queryType = null)
    {
        if ($queryType === null) {
            $queryType = 'filter';
        }

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $permissionResolver = $repository->getPermissionResolver();
        $editorsUserGroupId = 13;

        $content = $this->createContentForTestUserMetadataGroupHorizontal();

        $criteria = [];
        $setupFactory = $this->getSetupFactory();

        // Do not limit for LSE, as it does not not require reindexing.
        // See explanation below.
        if ($setupFactory instanceof LegacySolrSetupFactory) {
            $criteria[] = new Criterion\ContentTypeIdentifier('folder');
        }

        $criteria[] = new Criterion\UserMetadata(
            Criterion\UserMetadata::GROUP,
            Criterion\Operator::EQ,
            $editorsUserGroupId
        );

        /** @var string $queryType */
        $query = new LocationQuery(
            [
                $queryType => new Criterion\LogicalAnd($criteria),
                'sortClauses' => [
                    new SortClause\Location\Id(),
                ],
                'limit' => 50,
            ]
        );

        if ($setupFactory instanceof LegacySolrSetupFactory) {
            $result = $searchService->findLocations($query);

            // Administrator User is owned by itself, when additional Locations are added
            // it should be reindexed and its UserGroups will updated, which means it should
            // also be found as a Content of Editors UserGroup. However we do not handle this
            // in listeners yet, and also miss SPI methods to do it without using Search (also
            // needed to decouple services), because as indexing is asynchronous Search
            // should not eat its own dog food for reindexing.
            self::assertEquals(1, $result->totalCount);

            self::assertEquals(
                $content->contentInfo->mainLocationId,
                $result->searchHits[0]->valueObject->id
            );
        } else {
            // This is how it should eventually work for all search engines,
            // with required reindexing listeners properly implemented.

            $result = $searchService->findLocations($query);

            // Assert last two hits manually, as ids will change because they are created
            // in test and not present in base fixture.
            $foundLocation1 = array_pop($result->searchHits);
            $foundLocation2 = array_pop($result->searchHits);
            // Remove additional Administrators UserGroup Location
            array_pop($result->searchHits);
            $result->totalCount = $result->totalCount - 2;
            self::assertEquals(
                $content->versionInfo->contentInfo->mainLocationId,
                $foundLocation1->valueObject->id
            );
            self::assertEquals(
                $permissionResolver->getCurrentUserReference()->getUserId(),
                $foundLocation2->valueObject->contentId
            );

            $this->simplifySearchResult($result);
            self::assertEqualsWithDelta(
                include $this->getFixtureDir() . '/UserMetadataLocation.php',
                $result,
                .1, // Be quite generous regarding delay -- most important for scores
                'Search results do not match.',
            );
        }
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testUserMetadataGroupHorizontalQueryLocation()
    {
        $this->testUserMetadataGroupHorizontalFilterLocation('query');
    }

    /**
     * Test for FullText on the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testFullTextOnNewContent()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );

        $contentCreateStruct->setField('name', 'foxes');

        $englishContent = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        $this->refreshSearch($repository);

        $query = new Query(
            [
                'query' => new Criterion\FullText('foxes'),
            ]
        );

        $searchResult = $searchService->findContentInfo($query);

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($englishContent->id, $searchResult->searchHits[0]->valueObject->id);
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testLanguageAnalysisSeparateContent()
    {
        self::markTestSkipped('Language analysis is currently not supported');

        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();
        $languageService = $repository->getContentLanguageService();

        $languageCreateStruct = $languageService->newLanguageCreateStruct();
        $languageCreateStruct->languageCode = 'rus-RU';
        $languageCreateStruct->name = 'Russian';

        $languageService->createLanguage($languageCreateStruct);

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );

        $contentCreateStruct->setField('name', 'foxes');

        $englishContent = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'rus-RU'
        );

        $contentCreateStruct->setField('name', 'foxes');

        $russianContent = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        // Only Content in English should be found, because Content in Russian
        // will not be correctly stemmed
        $query = new Query(
            [
                'query' => new Criterion\FullText('foxing'),
            ]
        );

        $searchResult = $searchService->findContent($query);

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($englishContent->id, $searchResult->searchHits[0]->valueObject->id);
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testLanguageAnalysisSameContent()
    {
        self::markTestSkipped('Language analysis is currently not supported');

        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();
        $languageService = $repository->getContentLanguageService();

        $languageCreateStruct = $languageService->newLanguageCreateStruct();
        $languageCreateStruct->languageCode = 'rus-RU';
        $languageCreateStruct->name = 'Russian';

        $languageService->createLanguage($languageCreateStruct);

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );

        $contentCreateStruct->setField('name', 'foxes важнейшими', 'eng-GB');
        $contentCreateStruct->setField('name', 'foxes важнейшими', 'rus-RU');

        $mixedContent = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        // Content will be found because translation in Russian will be correctly stemmed
        $query = new Query(
            [
                'query' => new Criterion\FullText('важнее'),
            ]
        );

        $searchResult = $searchService->findContent($query);

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($mixedContent->id, $searchResult->searchHits[0]->valueObject->id);
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testLanguageAnalysisSameContentNotFound()
    {
        self::markTestSkipped('Language analysis is currently not supported');

        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();
        $languageService = $repository->getContentLanguageService();

        $languageCreateStruct = $languageService->newLanguageCreateStruct();
        $languageCreateStruct->languageCode = 'rus-RU';
        $languageCreateStruct->name = 'Russian';

        $languageService->createLanguage($languageCreateStruct);

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );

        $contentCreateStruct->setField('name', 'foxes важнейшими', 'eng-GB');
        $contentCreateStruct->setField('name', 'foxes важнейшими', 'rus-RU');

        $mixedContent = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        // Content should be found because translation in Russian will be correctly stemmed
        $query = new Query(
            [
                'query' => new Criterion\FullText('важнее'),
            ]
        );

        // Filtering fields for only English will cause no match because the term will
        // not be correctly stemmed
        $searchResult = $searchService->findContent($query, ['languages' => ['eng-GB']]);

        self::assertEquals(0, $searchResult->totalCount);
    }

    /**
     * Test for the findContent() method searching for content filtered by languages.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent
     */
    public function testFindContentWithLanguageFilter()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $query = new Query(
            [
                'filter' => new Criterion\ContentId([4]),
                'offset' => 0,
            ]
        );
        $searchResult = $searchService->findContent(
            $query,
            ['languages' => ['eng-US']],
            false
        );
        /* END: Use Case */

        self::assertInstanceOf(
            SearchResult::class,
            $searchResult
        );

        self::assertEquals(1, $searchResult->totalCount);
        self::assertNotNull($searchResult->totalCount);
        self::assertCount($searchResult->totalCount, $searchResult->searchHits);
        foreach ($searchResult->searchHits as $searchHit) {
            self::assertInstanceOf(
                SearchHit::class,
                $searchHit
            );
        }
    }

    /**
     * This test prepares data for other tests.
     *
     * @see testFulltextContentSearchComplex
     * @see testFulltextLocationSearchComplex
     *
     * @return array
     */
    public function testFulltextComplex()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');

        $contentCreateStruct->setField('name', 'red');
        $contentCreateStruct->setField('short_name', 'red apple');
        $content1 = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        $contentCreateStruct->setField('name', 'apple');
        $contentCreateStruct->setField('short_name', 'two');
        $content2 = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        $contentCreateStruct->setField('name', 'red apple');
        $contentCreateStruct->setField('short_name', 'three');
        $content3 = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        $contentCreateStruct->setField('name', 'four');
        $contentCreateStruct->setField('name', 'german red apple', 'ger-DE');
        $contentCreateStruct->setField('short_name', 'four');
        $contentCreateStruct->setField('short_name', 'german red apple', 'ger-DE');
        $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        $this->refreshSearch($repository);

        $criterion = new Criterion\FullText(
            'red apple',
            [
                'boost' => [
                    'short_name' => 2,
                ],
                'fuzziness' => .1,
            ]
        );

        return [$criterion, $content1, $content2, $content3];
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @depends testFulltextComplex
     *
     * @param array $data
     */
    public function testFulltextContentSearchComplex(array $data)
    {
        // Do not initialize from scratch
        $repository = $this->getRepository(false);
        $searchService = $repository->getSearchService();
        list($criterion, $content1, $content2, $content3) = $data;

        $searchResult = $searchService->findContent(
            new Query(['query' => $criterion]),
            ['languages' => ['eng-GB']]
        );
        $searchHits = $searchResult->searchHits;

        self::assertEquals(3, $searchResult->totalCount);

        // Legacy search engine does have scoring, sorting the results by ID in that case
        $setupFactory = $this->getSetupFactory();
        if ($setupFactory instanceof Legacy) {
            $this->sortSearchHitsById($searchHits);

            self::assertEquals($content1->id, $searchHits[0]->valueObject->id);
            self::assertEquals($content2->id, $searchHits[1]->valueObject->id);
            self::assertEquals($content3->id, $searchHits[2]->valueObject->id);

            return;
        }

        // Assert scores are descending
        self::assertGreaterThan($searchHits[1]->score, $searchHits[0]->score);
        self::assertGreaterThan($searchHits[2]->score, $searchHits[1]->score);

        // Assert order
        self::assertEquals($content1->id, $searchHits[0]->valueObject->id);
        self::assertEquals($content3->id, $searchHits[1]->valueObject->id);
        self::assertEquals($content2->id, $searchHits[2]->valueObject->id);
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @depends testFulltextComplex
     *
     * @param array $data
     */
    public function testFulltextContentTranslationSearch(array $data)
    {
        $criterion = $data[0];
        $query = new Query(['query' => $criterion]);

        $this->assertFulltextSearchForTranslations(self::FIND_CONTENT_METHOD, $query);
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @depends testFulltextComplex
     *
     * @param array $data
     */
    public function testFulltextLocationSearchComplex(array $data)
    {
        $setupFactory = $this->getSetupFactory();
        if ($setupFactory instanceof LegacySolrSetupFactory && getenv('SOLR_VERSION') === '4.10.4') {
            self::markTestSkipped('Skipping location search score test on Solr 4.10, you need Solr 6 for this!');
        }

        // Do not initialize from scratch
        $repository = $this->getRepository(false);
        list($criterion, $content1, $content2, $content3) = $data;
        $searchService = $repository->getSearchService();

        $searchResult = $searchService->findLocations(
            new LocationQuery(['query' => $criterion]),
            ['languages' => ['eng-GB']]
        );
        $searchHits = $searchResult->searchHits;

        self::assertEquals(3, $searchResult->totalCount);

        // Legacy search engine does have scoring, sorting the results by ID in that case
        $setupFactory = $this->getSetupFactory();
        if ($setupFactory instanceof Legacy) {
            $this->sortSearchHitsById($searchHits);

            self::assertEquals($content1->id, $searchHits[0]->valueObject->contentId);
            self::assertEquals($content2->id, $searchHits[1]->valueObject->contentId);
            self::assertEquals($content3->id, $searchHits[2]->valueObject->contentId);

            return;
        }

        // Assert scores are descending
        self::assertGreaterThan($searchHits[1]->score, $searchHits[0]->score);
        self::assertGreaterThan($searchHits[2]->score, $searchHits[1]->score);

        // Assert order
        self::assertEquals($content1->id, $searchHits[0]->valueObject->contentId);
        self::assertEquals($content3->id, $searchHits[1]->valueObject->contentId);
        self::assertEquals($content2->id, $searchHits[2]->valueObject->contentId);
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @depends testFulltextComplex
     *
     * @param array $data
     */
    public function testFulltextLocationTranslationSearch(array $data): void
    {
        $criterion = $data[0];
        $query = new LocationQuery(['query' => $criterion]);

        $this->assertFulltextSearchForTranslations(self::FIND_LOCATION_METHOD, $query);
    }

    public function testSpellcheckWithIncorrectQuery(): void
    {
        $searchService = $this->getRepository()->getSearchService();

        if (!$searchService->supports(SearchService::CAPABILITY_SPELLCHECK)) {
            self::markTestSkipped("Search engine doesn't support spellchecking");
        }

        $query = new Query();
        // Search phrase with typo: "Contatc Us" instead of "Contact Us":
        $query->spellcheck = new Query\Spellcheck('Contatc Us');

        $results = $searchService->findContent($query);

        self::assertNotNull($results->spellcheck);
        self::assertTrue($results->spellcheck->isIncorrect());
        self::assertEqualsIgnoringCase('Contact Us', $results->spellcheck->getQuery());
    }

    public function testSpellcheckWithCorrectQuery(): void
    {
        $searchService = $this->getRepository()->getSearchService();

        if (!$searchService->supports(SearchService::CAPABILITY_SPELLCHECK)) {
            self::markTestSkipped("Search engine doesn't support spellchecking");
        }

        $query = new Query();
        // Search phrase without typo
        $query->spellcheck = new Query\Spellcheck('Ibexa Platform');

        $results = $searchService->findContent($query);

        self::assertNotNull($results->spellcheck);
        self::assertFalse($results->spellcheck->isIncorrect());
        self::assertEqualsIgnoringCase('Ibexa Platform', $results->spellcheck->getQuery());
    }

    /**
     * Assert that query result matches the given fixture.
     *
     * @throws \ReflectionException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function assertQueryFixture(
        Query $query,
        string $fixtureFilePath,
        ?callable $closure = null,
        bool $ignoreScore = true,
        bool $info = false,
        bool $id = true
    ): void {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        try {
            if ($query instanceof LocationQuery) {
                $result = $searchService->findLocations($query);
            } elseif ($query instanceof Query) {
                if ($info) {
                    $result = $searchService->findContentInfo($query);
                } else {
                    $result = $searchService->findContent($query);
                }
            } else {
                self::fail('Expected instance of LocationQuery or Query, got: ' . gettype($query));
            }
            $this->simplifySearchResult($result);
        } catch (NotImplementedException $e) {
            self::markTestSkipped(
                'This feature is not supported by the current search backend: ' . $e->getMessage()
            );
        }

        if (!is_file($fixtureFilePath)) {
            if (isset($_ENV['ibexa_tests_record'])) {
                file_put_contents(
                    $record = $fixtureFilePath . '.recording',
                    "<?php\n\nreturn " . var_export($result, true) . ";\n\n"
                );
                self::markTestIncomplete("No fixture available. Result recorded at $record. Result: \n" . $this->printResult($result));
            } else {
                self::markTestIncomplete("No fixture available. Set \$_ENV['ibexa_tests_record'] to generate:\n " . $fixtureFilePath);
            }
        }

        $fixture = require $fixtureFilePath;

        if ($closure !== null) {
            $closure($fixture);
            $closure($result);
        }

        if ($ignoreScore) {
            foreach ([$fixture, $result] as $set) {
                $property = new \ReflectionProperty(get_class($set), 'maxScore');
                $property->setAccessible(true);
                $property->setValue($set, 0.0);

                foreach ($set->searchHits as $hit) {
                    $property = new \ReflectionProperty(get_class($hit), 'score');
                    $property->setAccessible(true);
                    $property->setValue($hit, 0.0);
                }
            }
        }

        foreach ([$fixture, $result] as $set) {
            foreach ($set->searchHits as $hit) {
                $property = new \ReflectionProperty(get_class($hit), 'index');
                $property->setAccessible(true);
                $property->setValue($hit, null);

                $property = new \ReflectionProperty(get_class($hit), 'matchedTranslation');
                $property->setAccessible(true);
                $property->setValue($hit, null);

                if (!$id) {
                    $hit->valueObject['id'] = null;
                }
            }
        }

        self::assertEqualsWithDelta(
            $fixture,
            $result,
            .99, // Be quite generous regarding delay -- most important for scores
            'Search results do not match the fixture: ' . $fixtureFilePath
        );
    }

    /**
     * Show a simplified view of the search result for manual introspection.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult $result
     *
     * @return string
     */
    protected function printResult(SearchResult $result): string
    {
        $printed = '';
        foreach ($result->searchHits as $hit) {
            $printed .= sprintf(" - %s (%s)\n", $hit->valueObject['title'], $hit->valueObject['id']);
        }

        return $printed;
    }

    /**
     * Simplify search result.
     *
     * This leads to saner comparisons of results, since we do not get the full
     * content objects every time.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult $result
     */
    protected function simplifySearchResult(SearchResult $result)
    {
        $result->time = 1;

        foreach ($result->searchHits as $hit) {
            switch (true) {
                case $hit->valueObject instanceof Content:
                case $hit->valueObject instanceof Location:
                    /** @phpstan-ignore assign.propertyType */
                    $hit->valueObject = [
                        /** @phpstan-var \Ibexa\Contracts\Core\Repository\Values\Content\Location|\Ibexa\Contracts\Core\Repository\Values\Content\Content $hit->valueObject */
                        'id' => $hit->valueObject->contentInfo->getId(),
                        /** @phpstan-var \Ibexa\Contracts\Core\Repository\Values\Content\Location|\Ibexa\Contracts\Core\Repository\Values\Content\Content $hit->valueObject */
                        'title' => $hit->valueObject->contentInfo->getName(),
                    ];
                    break;

                case $hit->valueObject instanceof ContentInfo:
                    /** @phpstan-ignore assign.propertyType */
                    $hit->valueObject = [
                        /** @phpstan-var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $hit->valueObject */
                        'id' => $hit->valueObject->id,
                        /** @phpstan-var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $hit->valueObject */
                        'title' => $hit->valueObject->name,
                    ];
                    break;

                default:
                    throw new \RuntimeException('Unknown search result hit type: ' . get_class($hit->valueObject));
            }
        }
    }

    /**
     * Get fixture directory.
     *
     * @return string
     */
    protected function getFixtureDir(): string
    {
        return __DIR__ . '/_fixtures/' . getenv('fixtureDir') . '/';
    }

    /**
     * For findContentInfo tests, to reuse fixtures for findContent tests.
     *
     * @param callable|null $closure
     *
     * @return callable
     */
    private function getContentInfoFixtureClosure($closure = null)
    {
        /** @var $data \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult */
        return static function (&$data) use ($closure) {
            foreach ($data->searchHits as $searchHit) {
                if ($searchHit->valueObject instanceof Content) {
                    $searchHit->valueObject = $searchHit->valueObject->getVersionInfo()->getContentInfo();
                }
            }

            if (isset($closure)) {
                $closure($data);
            }
        };
    }

    /**
     * Test searching using Field Criterion where the given Field Identifier exists in
     * both searchable and non-searchable Fields.
     * Number of returned results depends on used storage.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     */
    public function testFieldCriterionForContentsWithIdenticalFieldIdentifiers()
    {
        $this->createContentWithFieldType(
            'url',
            'title',
            'foo'
        );
        $this->createContentWithFieldType(
            'string',
            'title',
            'foo'
        );
        $query = new Query(
            [
                'query' => new Criterion\Field(
                    'title',
                    Criterion\Operator::EQ,
                    'foo'
                ),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findContent($query);

        self::assertTrue(($result->totalCount === 1 || $result->totalCount === 2));
    }

    private function createContentWithFieldType(
        string $fieldType,
        string $fieldName,
        string $fieldValue
    ) {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct($fieldType . uniqid());
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->remoteId = $fieldType . '-123';
        $createStruct->names = ['eng-GB' => $fieldType];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new \DateTime();

        $fieldCreate = $contentTypeService->newFieldDefinitionCreateStruct($fieldName, 'ibexa_' . $fieldType);
        $fieldCreate->names = ['eng-GB' => $fieldName];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = 1;

        $createStruct->addFieldDefinition($fieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->remoteId = $fieldType . '-456';
        $createStruct->alwaysAvailable = false;
        $createStruct->setField(
            $fieldName,
            $fieldValue
        );

        $draft = $contentService->createContent($createStruct);
        $content = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        return $content;
    }

    /**
     * Test for the findContent() method with random sort clause.
     *
     * There is a slight chance when this test could fail, if by some reason,
     * we got to same _random_ results, or mt_rand() provides same seed for seed-supported DB implementation.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent()
     *
     * @dataProvider getSeedsForRandomSortClause
     */
    public function testRandomSortContent(?int $firstSeed, ?int $secondSeed)
    {
        if ($firstSeed || $secondSeed) {
            $this->skipIfSeedNotImplemented();
        }

        $firstQuery = new Query([
            'sortClauses' => [
                new SortClause\Random($firstSeed),
            ],
        ]);

        $secondQuery = new Query([
            'sortClauses' => [
                new SortClause\Random($secondSeed),
            ],
        ]);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $method = 'assertNotEquals';

        if ($firstSeed !== null && $secondSeed !== null && ($firstSeed === $secondSeed)) {
            $method = 'assertEquals';
        }

        try {
            $this->$method(
                $searchService->findContent($firstQuery)->searchHits,
                $searchService->findContent($secondQuery)->searchHits
            );
        } catch (NotImplementedException $e) {
            self::markTestSkipped(
                'This feature is not supported by the current search backend: ' . $e->getMessage()
            );
        }
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @dataProvider getSeedsForRandomSortClause
     */
    public function testRandomSortLocation(?int $firstSeed, ?int $secondSeed)
    {
        if ($firstSeed || $secondSeed) {
            $this->skipIfSeedNotImplemented();
        }

        $firstQuery = new LocationQuery([
            'sortClauses' => [
                new SortClause\Random($firstSeed),
            ],
        ]);

        $secondQuery = new LocationQuery([
            'sortClauses' => [
                new SortClause\Random($secondSeed),
            ],
        ]);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $method = 'assertNotEquals';

        if ($firstSeed !== null && $secondSeed !== null && ($firstSeed === $secondSeed)) {
            $method = 'assertEquals';
        }

        try {
            $this->$method(
                $searchService->findLocations($firstQuery)->searchHits,
                $searchService->findLocations($secondQuery)->searchHits
            );
        } catch (NotImplementedException $e) {
            self::markTestSkipped(
                'This feature is not supported by the current search backend: ' . $e->getMessage()
            );
        }
    }

    public function getSeedsForRandomSortClause()
    {
        $randomSeed = mt_rand();

        return [
            [
                null,
                null,
            ],
            [
                123456,
                123456,
            ],
            [
               $randomSeed,
               2 * $randomSeed,
            ],
        ];
    }

    private function skipIfSeedNotImplemented()
    {
        /** @var \Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy $setupFactory */
        $setupFactory = $this->getSetupFactory();

        $db = $setupFactory->getDB();

        if (in_array($db, ['sqlite', 'pgsql'])) {
            self::markTestSkipped(
                'Seed function is not implemented in ' . $db . '.'
            );
        }
    }

    /**
     * @param string $findMethod
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query $query
     * @param array $languages
     * @param bool $useAlwaysAvailable
     *
     * @throws \InvalidArgumentException
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult
     */
    private function find(
        string $findMethod,
        Query $query,
        array $languages,
        bool $useAlwaysAvailable
    ): SearchResult {
        if (false === in_array($findMethod, self::AVAILABLE_FIND_METHODS, true)) {
            throw new \InvalidArgumentException(
                'Allowed find methods are: '
                . implode(',', self::AVAILABLE_FIND_METHODS)
            );
        }

        $repository = $this->getRepository(false);
        $searchService = $repository->getSearchService();

        return $searchService->{$findMethod}(
            $query,
            [
                'languages' => $languages,
                'useAlwaysAvailable' => $useAlwaysAvailable,
            ]
        );
    }

    /**
     * @param string $findMethod
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query $query
     */
    private function assertFulltextSearchForTranslations(string $findMethod, Query $query): void
    {
        /*
         * Search in German translations without always available
         */
        $searchResult = $this->find($findMethod, $query, ['ger-DE'], false);
        self::assertEquals(1, $searchResult->totalCount);
        $this->assertSearchResultMatchTranslations($searchResult, ['ger-DE']);

        /*
         * Search in German translations with always available
         */
        $searchResult = $this->find($findMethod, $query, ['ger-DE'], true);
        self::assertEquals(4, $searchResult->totalCount);
        $this->assertSearchResultMatchTranslations($searchResult, ['eng-GB', 'eng-GB', 'eng-GB', 'ger-DE']);

        /*
         * Search in multiple (ger-DE, eng-GB) translations without always available
         */
        $searchResult = $this->find($findMethod, $query, ['ger-DE', 'eng-GB'], false);
        self::assertEquals(4, $searchResult->totalCount);
        $this->assertSearchResultMatchTranslations($searchResult, ['eng-GB', 'eng-GB', 'eng-GB', 'ger-DE']);

        /*
         * Search in multiple (eng-US, ger-DE) translations without always available
         */
        $searchResult = $this->find($findMethod, $query, ['eng-US', 'ger-DE'], false);
        self::assertEquals(1, $searchResult->totalCount);
        $this->assertSearchResultMatchTranslations($searchResult, ['ger-DE']);

        /*
         * Search in eng-US translations without always available
         */
        $searchResult = $this->find($findMethod, $query, ['eng-US'], false);
        self::assertEquals(0, $searchResult->totalCount);

        /*
         * Search in eng-US translations with always available
         */
        $searchResult = $this->find($findMethod, $query, ['eng-US'], true);
        self::assertEquals(3, $searchResult->totalCount);
        $this->assertSearchResultMatchTranslations($searchResult, ['eng-GB', 'eng-GB', 'eng-GB']);
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult $searchResult
     * @param string[] $translationsToMatch
     *
     * @throws \InvalidArgumentException
     */
    private function assertSearchResultMatchTranslations(
        SearchResult $searchResult,
        array $translationsToMatch
    ): void {
        $translationsToMatchCount = count($translationsToMatch);

        if ($searchResult->totalCount < $translationsToMatchCount
            || $searchResult->totalCount > $translationsToMatchCount
        ) {
            throw new \InvalidArgumentException(
                'Argument `translationsToMatch` must be equal to the search result total count!'
            );
        }

        $searchHits = $searchResult->searchHits;
        $this->sortSearchHitsById($searchHits);

        for ($i = 0; $i < $searchResult->totalCount; ++$i) {
            self::assertEquals(
                $translationsToMatch[$i],
                $searchHits[$i]->matchedTranslation
            );
        }
    }

    private function sortSearchHitsById(array &$searchHits): void
    {
        usort(
            $searchHits,
            static function (SearchHit $a, SearchHit $b): int {
                return $a->valueObject->id <=> $b->valueObject->id;
            }
        );
    }

    /**
     * @dataProvider providerForTestSortingByNumericFieldsWithValuesOfDifferentLength
     *
     * @param int[] $expectedOrderedIds
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testSortingByNumericFieldsWithValuesOfDifferentLength(
        LocationQuery $query,
        array $expectedOrderedIds
    ): void {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $result = $searchService->findLocations($query);

        self::assertEquals(count($expectedOrderedIds), $result->totalCount);
        $actualIds = array_map(
            static function (SearchHit $searchHit) {
                /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location $location */
                $location = $searchHit->valueObject;

                return $location->id;
            },
            $result->searchHits
        );
        self::assertEquals($expectedOrderedIds, $actualIds);
    }

    public function providerForTestSortingByNumericFieldsWithValuesOfDifferentLength(): iterable
    {
        yield 'Location ID ASC' => [
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([43, 5]),
                    'sortClauses' => [
                        new SortClause\Location\Id(LocationQuery::SORT_ASC),
                    ],
                ]
            ),
            [5, 43],
        ];

        yield 'Location ID DESC' => [
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([5, 43]),
                    'sortClauses' => [
                        new SortClause\Location\Id(LocationQuery::SORT_DESC),
                    ],
                ]
            ),
            [43, 5],
        ];

        yield 'Content ID ASC' => [
            new LocationQuery(
                [
                    'filter' => new Criterion\ContentId([14, 4]),
                    'sortClauses' => [
                        new SortClause\ContentId(LocationQuery::SORT_ASC),
                    ],
                ]
            ),
            // those are still Location IDs as it's LocationQuery
            [5, 15],
        ];

        yield 'Content ID DESC' => [
            new LocationQuery(
                [
                    'filter' => new Criterion\ContentId([4, 14]),
                    'sortClauses' => [
                        new SortClause\ContentId(LocationQuery::SORT_DESC),
                    ],
                ]
            ),
            // those are still Location IDs as it's LocationQuery
            [15, 5],
        ];
    }

    private function isRunningOnLegacySetup(): bool
    {
        return get_class($this->getSetupFactory()) === Legacy::class;
    }
}
