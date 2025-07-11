<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Exception;
use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard as SPIURLWildcard;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardTranslationResult;
use Ibexa\Core\Base\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Core\Repository\URLWildcardService;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;

/**
 * Mock Test case for UrlWildcard Service.
 */
class UrlWildcardTest extends BaseServiceMockTest
{
    private const EXAMPLE_URL_WILDCARD_ID = 1;

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\UrlWildcard\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $urlWildcardHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->urlWildcardHandler = $this->getPersistenceMockHandler('Content\\UrlWildcard\\Handler');
        $this->permissionResolver = $this->getPermissionResolverMock();
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create
     */
    public function testCreateThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->permissionResolver->expects(
            self::once()
        )->method(
            'hasAccess'
        )->with(
            self::equalTo('content'),
            self::equalTo('urltranslator')
        )->will(
            self::returnValue(false)
        );

        $this->expectException(UnauthorizedException::class);

        $mockedService->create('lorem/ipsum', 'opossum', true);
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create
     */
    public function testCreateThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->permissionResolver->expects(
            self::once()
        )->method(
            'hasAccess'
        )->with(
            self::equalTo('content'),
            self::equalTo('urltranslator')
        )->will(
            self::returnValue(true)
        );

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'exactSourceUrlExists'
        )->willReturn(true);

        $this->expectException(InvalidArgumentException::class);

        $mockedService->create('/lorem/ipsum', 'opossum', true);
    }

    public function providerForTestCreateThrowsContentValidationException()
    {
        return [
            ['fruit', 'food/{1}', true],
            ['fruit/*', 'food/{2}', false],
            ['fruit/*/*', 'food/{3}', true],
        ];
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create
     *
     * @dataProvider providerForTestCreateThrowsContentValidationException
     */
    public function testCreateThrowsContentValidationException($sourceUrl, $destinationUrl, $forward)
    {
        $this->expectException(ContentValidationException::class);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->permissionResolver->expects(
            self::once()
        )->method(
            'hasAccess'
        )->with(
            self::equalTo('content'),
            self::equalTo('urltranslator')
        )->will(
            self::returnValue(true)
        );

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'exactSourceUrlExists'
        )->willReturn(false);

        $this->expectException(ContentValidationException::class);

        $mockedService->create($sourceUrl, $destinationUrl, $forward);
    }

    public function providerForTestCreate()
    {
        return [
            ['fruit', 'food', true],
            [' /fruit/ ', ' /food/ ', true],
            ['/fruit/*', '/food', false],
            ['/fruit/*', '/food/{1}', true],
            ['/fruit/*/*', '/food/{1}', true],
            ['/fruit/*/*', '/food/{2}', true],
            ['/fruit/*/*', '/food/{1}/{2}', true],
        ];
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create
     *
     * @dataProvider providerForTestCreate
     */
    public function testCreate($sourceUrl, $destinationUrl, $forward)
    {
        $mockedService = $this->getPartlyMockedURLWildcardService();

        $sourceUrl = '/' . trim($sourceUrl, '/ ');
        $destinationUrl = '/' . trim($destinationUrl, '/ ');

        $this->permissionResolver->expects(
            self::once()
        )->method(
            'hasAccess'
        )->with(
            self::equalTo('content'),
            self::equalTo('urltranslator')
        )->will(
            self::returnValue(true)
        );

        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock->expects(self::once())->method('beginTransaction');
        $repositoryMock->expects(self::once())->method('commit');

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'exactSourceUrlExists'
        )->willReturn(false);

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'create'
        )->with(
            self::equalTo($sourceUrl),
            self::equalTo($destinationUrl),
            self::equalTo($forward)
        )->will(
            self::returnValue(
                new SPIURLWildcard(
                    [
                        'id' => 123456,
                        'sourceUrl' => $sourceUrl,
                        'destinationUrl' => $destinationUrl,
                        'forward' => $forward,
                    ]
                )
            )
        );

        $urlWildCard = $mockedService->create($sourceUrl, $destinationUrl, $forward);

        self::assertEquals(
            new URLWildcard(
                [
                    'id' => 123456,
                    'sourceUrl' => $sourceUrl,
                    'destinationUrl' => $destinationUrl,
                    'forward' => $forward,
                ]
            ),
            $urlWildCard
        );
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create
     */
    public function testCreateWithRollback()
    {
        $this->expectException(\Exception::class);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->permissionResolver->expects(
            self::once()
        )->method(
            'hasAccess'
        )->with(
            self::equalTo('content'),
            self::equalTo('urltranslator')
        )->will(
            self::returnValue(true)
        );

        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock->expects(self::once())->method('beginTransaction');
        $repositoryMock->expects(self::once())->method('rollback');

        $sourceUrl = '/lorem';
        $destinationUrl = '/ipsum';
        $forward = true;

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'exactSourceUrlExists'
        )->willReturn(false);

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'create'
        )->with(
            self::equalTo($sourceUrl),
            self::equalTo($destinationUrl),
            self::equalTo($forward)
        )->will(
            self::throwException(new Exception())
        );

        $this->expectException(Exception::class);

        $mockedService->create($sourceUrl, $destinationUrl, $forward);
    }

    /**
     * Test for the remove() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::remove
     */
    public function testRemoveThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $wildcard = new URLWildcard(['id' => self::EXAMPLE_URL_WILDCARD_ID]);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->permissionResolver->expects(
            self::once()
        )->method(
            'canUser'
        )->with(
            self::equalTo('content'),
            self::equalTo('urltranslator'),
            self::equalTo($wildcard)
        )->will(
            self::returnValue(false)
        );

        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock->expects(self::never())->method('beginTransaction');

        $this->expectException(UnauthorizedException::class);

        $mockedService->remove($wildcard);
    }

    /**
     * Test for the remove() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::remove
     */
    public function testRemove()
    {
        $wildcard = new URLWildcard(['id' => self::EXAMPLE_URL_WILDCARD_ID]);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->permissionResolver->expects(
            self::once()
        )->method(
            'canUser'
        )->with(
            self::equalTo('content'),
            self::equalTo('urltranslator'),
            self::equalTo($wildcard)
        )->will(
            self::returnValue(true)
        );

        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock->expects(self::once())->method('beginTransaction');
        $repositoryMock->expects(self::once())->method('commit');

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'remove'
        )->with(
            self::equalTo(self::EXAMPLE_URL_WILDCARD_ID)
        );

        $mockedService->remove($wildcard);
    }

    /**
     * Test for the remove() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::remove
     */
    public function testRemoveWithRollback()
    {
        $this->expectException(\Exception::class);

        $wildcard = new URLWildcard(['id' => self::EXAMPLE_URL_WILDCARD_ID]);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->permissionResolver->expects(
            self::once()
        )->method(
            'canUser'
        )->with(
            self::equalTo('content'),
            self::equalTo('urltranslator'),
            self::equalTo($wildcard)
        )->will(
            self::returnValue(true)
        );

        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock->expects(self::once())->method('beginTransaction');
        $repositoryMock->expects(self::once())->method('rollback');

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'remove'
        )->with(
            self::equalTo(self::EXAMPLE_URL_WILDCARD_ID)
        )->will(
            self::throwException(new Exception())
        );

        $this->expectException(Exception::class);

        $mockedService->remove($wildcard);
    }

    /**
     * Test for the load() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::remove
     */
    public function testLoadThrowsException()
    {
        $this->expectException(\Exception::class);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'load'
        )->with(
            self::equalTo(self::EXAMPLE_URL_WILDCARD_ID)
        )->will(
            self::throwException(new Exception())
        );

        $this->expectException(Exception::class);

        $mockedService->load(self::EXAMPLE_URL_WILDCARD_ID);
    }

    /**
     * Test for the load() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::remove
     */
    public function testLoad()
    {
        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'load'
        )->with(
            self::equalTo(self::EXAMPLE_URL_WILDCARD_ID)
        )->will(
            self::returnValue(
                new SPIURLWildcard(
                    [
                        'id' => self::EXAMPLE_URL_WILDCARD_ID,
                        'sourceUrl' => 'this',
                        'destinationUrl' => 'that',
                        'forward' => true,
                    ]
                )
            )
        );

        $urlWildcard = $mockedService->load(self::EXAMPLE_URL_WILDCARD_ID);

        self::assertEquals(
            new URLWildcard(
                [
                    'id' => self::EXAMPLE_URL_WILDCARD_ID,
                    'sourceUrl' => 'this',
                    'destinationUrl' => 'that',
                    'forward' => true,
                ]
            ),
            $urlWildcard
        );
    }

    /**
     * Test for the loadAll() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::loadAll
     */
    public function testLoadAll()
    {
        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'loadAll'
        )->with(
            self::equalTo(0),
            self::equalTo(-1)
        )->will(
            self::returnValue([])
        );

        $mockedService->loadAll();
    }

    /**
     * Test for the loadAll() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::loadAll
     */
    public function testLoadAllWithLimitAndOffset()
    {
        $mockedService = $this->getPartlyMockedURLWildcardService();

        $this->urlWildcardHandler->expects(
            self::once()
        )->method(
            'loadAll'
        )->with(
            self::equalTo(12),
            self::equalTo(34)
        )->will(
            self::returnValue(
                [
                    new SPIURLWildcard(
                        [
                            'id' => self::EXAMPLE_URL_WILDCARD_ID,
                            'sourceUrl' => 'this',
                            'destinationUrl' => 'that',
                            'forward' => true,
                        ]
                    ),
                ]
            )
        );

        $urlWildcards = $mockedService->loadAll(12, 34);

        self::assertEquals(
            [
                new URLWildcard(
                    [
                        'id' => self::EXAMPLE_URL_WILDCARD_ID,
                        'sourceUrl' => 'this',
                        'destinationUrl' => 'that',
                        'forward' => true,
                    ]
                ),
            ],
            $urlWildcards
        );
    }

    /**
     * @return array
     */
    public function providerForTestTranslateThrowsNotFoundException()
    {
        return [
            [
                [
                    'sourceUrl' => '/fruit',
                    'destinationUrl' => '/food',
                    'forward' => true,
                ],
                '/vegetable',
            ],
            [
                [
                    'sourceUrl' => '/fruit/apricot',
                    'destinationUrl' => '/food/apricot',
                    'forward' => true,
                ],
                '/fruit/lemon',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*',
                    'destinationUrl' => '/food/{1}',
                    'forward' => true,
                ],
                '/fruit',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*/*',
                    'destinationUrl' => '/food/{1}/{2}',
                    'forward' => true,
                ],
                '/fruit/citrus',
            ],
        ];
    }

    /**
     * Test for the translate() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::translate
     *
     * @dataProvider providerForTestTranslateThrowsNotFoundException
     */
    public function testTranslateThrowsNotFoundException($createArray, $url)
    {
        $this->expectException(NotFoundException::class);

        $mockedService = $this->getPartlyMockedURLWildcardService();

        $trimmedUrl = trim($url, '/ ');

        $this->urlWildcardHandler
            ->expects(self::once())
            ->method('translate')
            ->with($trimmedUrl)
            ->willThrowException(new APINotFoundException('UrlWildcard', $trimmedUrl));

        $this->expectException(NotFoundException::class);

        $mockedService->translate($url);
    }

    /**
     * @return array
     */
    public function providerForTestTranslate()
    {
        return [
            [
                [
                    'sourceUrl' => '/fruit/apricot',
                    'destinationUrl' => '/food/apricot',
                    'forward' => true,
                ],
                '/fruit/apricot',
                '/food/apricot',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*',
                    'destinationUrl' => '/food/{1}',
                    'forward' => true,
                ],
                '/fruit/citrus',
                '/food/citrus',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*',
                    'destinationUrl' => '/food/{1}',
                    'forward' => true,
                ],
                '/fruit/citrus/orange',
                '/food/citrus/orange',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*/*',
                    'destinationUrl' => '/food/{2}',
                    'forward' => true,
                ],
                '/fruit/citrus/orange',
                '/food/orange',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*/*',
                    'destinationUrl' => '/food/{1}/{2}',
                    'forward' => true,
                ],
                '/fruit/citrus/orange',
                '/food/citrus/orange',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*/pamplemousse',
                    'destinationUrl' => '/food/weird',
                    'forward' => true,
                ],
                '/fruit/citrus/pamplemousse',
                '/food/weird',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*/pamplemousse',
                    'destinationUrl' => '/food/weird/{1}',
                    'forward' => true,
                ],
                '/fruit/citrus/pamplemousse',
                '/food/weird/citrus',
            ],
            [
                [
                    'sourceUrl' => '/fruit/*/pamplemousse',
                    'destinationUrl' => '/food/weird/{1}',
                    'forward' => true,
                ],
                '/fruit/citrus/yellow/pamplemousse',
                '/food/weird/citrus/yellow',
            ],
        ];
    }

    /**
     * Test for the translate() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::translate
     *
     * @dataProvider providerForTestTranslate
     */
    public function testTranslate($createArray, $url, $uri)
    {
        $mockedService = $this->getPartlyMockedURLWildcardService();

        $trimmedUrl = trim($url, '/ ');

        $this->urlWildcardHandler
            ->expects(self::once())
            ->method('translate')
            ->with($trimmedUrl)

            ->willReturn(new SPIURLWildcard([
                'sourceUrl' => $createArray['sourceUrl'],
                'destinationUrl' => $uri,
                'forward' => $createArray['forward'],
            ]));

        $translationResult = $mockedService->translate($url);

        self::assertEquals(
            new URLWildcardTranslationResult(
                [
                    'uri' => $uri,
                    'forward' => $createArray['forward'],
                ]
            ),
            $translationResult
        );
    }

    /**
     * Test for the translate() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::translate
     */
    public function testTranslateUsesLongestMatchingWildcard()
    {
        $mockedService = $this->getPartlyMockedURLWildcardService();

        $url = '/something/something/thing';
        $trimmedUrl = trim($url, '/ ');

        $this->urlWildcardHandler
            ->expects(self::once())
            ->method('translate')
            ->with($trimmedUrl)
            ->willReturn(new SPIURLWildcard([
                'destinationUrl' => '/long',
                'forward' => false,
            ]));

        $translationResult = $mockedService->translate($url);

        self::assertEquals(
            new URLWildcardTranslationResult(
                [
                    'uri' => '/long',
                    'forward' => false,
                ]
            ),
            $translationResult
        );
    }

    /**
     * Returns the content service to test with $methods mocked.
     *
     * Injected Repository comes from {@see getRepositoryMock()} and persistence handler from {@see getPersistenceMock()}
     *
     * @param string[] $methods
     *
     * @return \Ibexa\Core\Repository\URLWildcardService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPartlyMockedURLWildcardService(array $methods = null)
    {
        return $this->getMockBuilder(URLWildcardService::class)
            ->setMethods($methods)
            ->setConstructorArgs(
                [
                    $this->getRepositoryMock(),
                    $this->urlWildcardHandler,
                    $this->permissionResolver,
                ]
            )
            ->getMock();
    }
}
