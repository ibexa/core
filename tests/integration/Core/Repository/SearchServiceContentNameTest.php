<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\FieldType\TextLine\Value;
use Ibexa\Tests\Integration\Core\RepositorySearchTestCase;

final class SearchServiceContentNameTest extends RepositorySearchTestCase
{
    private const TOTAL_COUNT = 20;

    private const LANGUAGE_CODE_ENG = 'eng-GB';
    private const LANGUAGE_CODE_GER = 'ger-DE';

    private const CAR_ENG = 'Car';
    private const SPORTS_CAR_ENG = 'Sports car';
    private const TRUCK_ENG = 'TRUCK';

    private const CAR_GER = 'auto';
    private const SPORTS_CAR_GER = 'Sportwagen';
    private const TRUCK_GER = 'LASTWAGEN';

    private const CONTENT_ITEMS_MAP = [
        [
            'mainLanguageCode' => self::LANGUAGE_CODE_ENG,
            'name' => self::CAR_ENG,
            'translations' => [
                self::LANGUAGE_CODE_GER => self::CAR_GER,
            ],
        ],
        [
            'mainLanguageCode' => self::LANGUAGE_CODE_ENG,
            'name' => self::SPORTS_CAR_ENG,
            'translations' => [
                self::LANGUAGE_CODE_GER => self::SPORTS_CAR_GER,
            ],
        ],
        [
            'mainLanguageCode' => self::LANGUAGE_CODE_ENG,
            'name' => self::TRUCK_ENG,
            'translations' => [
                self::LANGUAGE_CODE_GER => self::TRUCK_GER,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestContentItems();

        $this->refreshSearch();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCriterionFindAllContentItems(): void
    {
        $query = $this->createQuery(
            $this->createContentNameCriterion('*')
        );

        self::assertSame(
            self::TOTAL_COUNT,
            self::getSearchService()->findContent($query)->totalCount
        );
    }

    /**
     * @dataProvider provideDataForTestCriterion
     *
     * @param array<string> $expectedContentItemTitles
     *
     * @throws InvalidArgumentException
     * @throws InvalidCriterionArgumentException
     */
    public function testCriterion(
        Criterion $criterion,
        ?string $languageCode,
        array $expectedContentItemTitles,
        int $expectedCount
    ): void {
        $result = self::getSearchService()->findContent(
            $this->createQuery($criterion),
            $this->getLanguageFilter($languageCode)
        );

        self::assertEquals(
            $expectedContentItemTitles,
            array_map(
                static function (SearchHit $searchHit) use ($languageCode): ?string {
                    $content = $searchHit->valueObject;
                    if ($content instanceof Content) {
                        return $content->getName($languageCode);
                    }

                    return null;
                },
                $result->searchHits
            )
        );

        self::assertSame(
            $expectedCount,
            $result->totalCount
        );
    }

    /**
     * @return iterable<array{
     *     Criterion,
     *     ?string,
     *     array<string>,
     *     int,
     * }>
     */
    public function provideDataForTestCriterion(): iterable
    {
        yield 'Content items not found' => [
            $this->createContentNameCriterion('foo'),
            self::LANGUAGE_CODE_ENG,
            [],
            0,
        ];

        yield 'Return content items in default language (English) that contain "car" in name' => [
            $this->createContentNameCriterion('*car*'),
            null,
            [
                self::CAR_ENG,
                self::SPORTS_CAR_ENG,
            ],
            2,
        ];

        yield 'Return content item in default language (English) whose name starts with "car"' => [
            $this->createContentNameCriterion('Car*'),
            null,
            [
                self::CAR_ENG,
            ],
            1,
        ];

        yield 'Return content item in English that contain "Spo*t*" in name' => [
            $this->createContentNameCriterion('Spo*t*'),
            self::LANGUAGE_CODE_ENG,
            [
                self::SPORTS_CAR_ENG,
            ],
            1,
        ];

        yield 'Return content item in English with name "sports car"' => [
            $this->createContentNameCriterion('sports car'),
            self::LANGUAGE_CODE_ENG,
            [
                self::SPORTS_CAR_ENG,
            ],
            1,
        ];

        yield 'Return content item in English that contain "**ruc*" in name' => [
            $this->createContentNameCriterion('**ruc*'),
            self::LANGUAGE_CODE_ENG,
            [
                self::TRUCK_ENG,
            ],
            1,
        ];

        yield 'Return content item in German that contain "aut*" in name' => [
            $this->createContentNameCriterion('aut*'),
            self::LANGUAGE_CODE_GER,
            [
                self::CAR_GER,
            ],
            1,
        ];

        yield 'Return content items in German that contain "*wagen" in name' => [
            $this->createContentNameCriterion('*wagen'),
            self::LANGUAGE_CODE_GER,
            [
                self::SPORTS_CAR_GER,
                self::TRUCK_GER,
            ],
            2,
        ];

        yield 'Return content item in German with name "lastwagen"' => [
            $this->createContentNameCriterion('lastwagen'),
            self::LANGUAGE_CODE_GER,
            [
                self::TRUCK_GER,
            ],
            1,
        ];
    }

    private function createTestContentItems(): void
    {
        foreach (self::CONTENT_ITEMS_MAP as $contentItem) {
            $this->createContent(
                $contentItem['name'],
                $contentItem['mainLanguageCode'],
                $contentItem['translations']
            );
        }
    }

    /**
     * @param array<string> $translations
     *
     * @return Content
     *
     * @throws BadStateException
     * @throws ContentFieldValidationException
     * @throws ContentValidationException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    private function createContent(
        string $title,
        string $mainLanguageCode,
        array $translations
    ): Content {
        $contentService = self::getContentService();
        $createStruct = $contentService->newContentCreateStruct(
            $this->loadContentType('article'),
            $mainLanguageCode
        );

        $createStruct->setField('title', new Value($title));

        if (!empty($translations)) {
            foreach ($translations as $languageCode => $translatedName) {
                $createStruct->setField('title', new Value($translatedName), $languageCode);
            }
        }

        $content = $contentService->createContent($createStruct);

        $contentService->publishVersion($content->getVersionInfo());

        return $content;
    }

    /**
     * @throws NotFoundException
     */
    private function loadContentType(string $contentTypeIdentifier): ContentType
    {
        return self::getContentTypeService()
            ->loadContentTypeByIdentifier($contentTypeIdentifier);
    }

    private function createContentNameCriterion(string $value): Criterion
    {
        return new Criterion\ContentName($value);
    }

    /**
     * @throws InvalidCriterionArgumentException
     */
    private function createQuery(Criterion $criterion): Query
    {
        $query = new Query();
        $query->filter = new Criterion\LogicalAnd(
            [$criterion]
        );

        return $query;
    }

    /**
     * @return array{}|array{
     *     languages: array<string>
     * }
     */
    public function getLanguageFilter(?string $languageCode): array
    {
        return null !== $languageCode
            ? ['languages' => [$languageCode]]
            : [];
    }
}
