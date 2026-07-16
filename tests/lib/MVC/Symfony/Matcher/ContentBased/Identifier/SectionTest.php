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
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\Section as SectionIdentifierMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SectionTest extends BaseTestCase
{
    /** @var SectionIdentifierMatcher */
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
     * @return MockObject
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
     * @dataProvider matchSectionProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\Section::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     * @covers \Ibexa\Core\MVC\RepositoryAware::setRepository
     *
     * @param string|string[] $matchingConfig
     * @param Repository $repository
     * @param bool $expectedResult
     */
    public function testMatchLocation(
        $matchingConfig,
        Repository $repository,
        $expectedResult
    ) {
        $this->matcher->setRepository($repository);
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

    public function matchSectionProvider()
    {
        return [
            [
                'foo',
                $this->generateRepositoryMockForSectionIdentifier('foo'),
                true,
            ],
            [
                'foo',
                $this->generateRepositoryMockForSectionIdentifier('bar'),
                false,
            ],
            [
                ['foo', 'baz'],
                $this->generateRepositoryMockForSectionIdentifier('bar'),
                false,
            ],
            [
                ['foo', 'baz'],
                $this->generateRepositoryMockForSectionIdentifier('baz'),
                true,
            ],
        ];
    }

    /**
     * @dataProvider matchSectionProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\Section::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     * @covers \Ibexa\Core\MVC\RepositoryAware::setRepository
     *
     * @param string|string[] $matchingConfig
     * @param Repository $repository
     * @param bool $expectedResult
     */
    public function testMatchContentInfo(
        $matchingConfig,
        Repository $repository,
        $expectedResult
    ) {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['sectionId' => 1]))
        );
    }
}
