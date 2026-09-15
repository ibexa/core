<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Identifier;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\SectionService;
use Ibexa\Contracts\Core\Repository\Values\Content\Section;
use Ibexa\Core\MVC\RepositoryAware;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\Section as SectionIdentifierMatcher;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversMethod(SectionIdentifierMatcher::class, 'matchLocation')]
#[CoversMethod(MultipleValued::class, 'setMatchingConfig')]
#[CoversMethod(RepositoryAware::class, 'setRepository')]
class SectionTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\Section */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new SectionIdentifierMatcher();
    }

    /**
     * Returns a Repository mock configured to return the appropriate Section object with given section identifier.
     *
     * @param string $sectionIdentifier
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function generateRepositoryMockForSectionIdentifier($sectionIdentifier)
    {
        $sectionServiceMock = $this->createMock(SectionService::class);
        $sectionServiceMock->expects(self::once())
            ->method('loadSection')
            ->will(
                self::returnValue(
                    $this
                        ->getMockBuilder(Section::class)
                        ->setConstructorArgs(
                            [
                                ['identifier' => $sectionIdentifier],
                            ]
                        )
                        ->getMockForAbstractClass()
                )
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::once())
            ->method('getSectionService')
            ->will(self::returnValue($sectionServiceMock));
        $repository
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->will(self::returnValue($this->getPermissionResolverMock()));

        return $repository;
    }

    /**
     * @param string|string[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchSectionProvider')]
    public function testMatchLocation($matchingConfig, string $sectionIdentifier, $expectedResult)
    {
        $this->matcher->setRepository($this->generateRepositoryMockForSectionIdentifier($sectionIdentifier));
        $this->matcher->setMatchingConfig($matchingConfig);

        $location = $this->getLocationMock();
        $location
            ->expects(self::once())
            ->method('getContentInfo')
            ->will(
                self::returnValue(
                    $this->getContentInfoMock(['sectionId' => 1])
                )
            );

        self::assertSame(
            $expectedResult,
            $this->matcher->matchLocation($location)
        );
    }

    public static function matchSectionProvider()
    {
        return [
            [
                'foo',
                'foo',
                true,
            ],
            [
                'foo',
                'bar',
                false,
            ],
            [
                ['foo', 'baz'],
                'bar',
                false,
            ],
            [
                ['foo', 'baz'],
                'baz',
                true,
            ],
        ];
    }

    /**
     * @param string|string[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchSectionProvider')]
    public function testMatchContentInfo($matchingConfig, string $sectionIdentifier, $expectedResult)
    {
        $this->matcher->setRepository($this->generateRepositoryMockForSectionIdentifier($sectionIdentifier));
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['sectionId' => 1]))
        );
    }
}
