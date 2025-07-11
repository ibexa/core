<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardTranslationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardUpdateStruct;

/**
 * Test case for operations in the URLWildcardService.
 *
 * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService
 *
 * @group url-wildcard
 */
class URLWildcardServiceTest extends BaseTestCase
{
    /**
     * Test for the create() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create()
     */
    public function testCreate()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create a new url wildcard
        $urlWildcard = $urlWildcardService->create('/articles/*', '/content/{1}');
        /* END: Use Case */

        self::assertInstanceOf(
            URLWildcard::class,
            $urlWildcard
        );

        return $urlWildcard;
    }

    /**
     * Test for the create() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard $urlWildcard
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create()
     *
     * @depends testCreate
     */
    public function testCreateSetsIdPropertyOnURLWildcard(URLWildcard $urlWildcard)
    {
        self::assertNotNull($urlWildcard->id);
    }

    /**
     * Test for the create() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard $urlWildcard
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create()
     *
     * @depends testCreate
     */
    public function testCreateSetsPropertiesOnURLWildcard(URLWildcard $urlWildcard)
    {
        $this->assertPropertiesCorrect(
            [
                'sourceUrl' => '/articles/*',
                'destinationUrl' => '/content/{1}',
                'forward' => false,
            ],
            $urlWildcard
        );
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create()
     *
     * @depends testCreate
     */
    public function testCreateWithOptionalForwardParameter()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create a new url wildcard
        $urlWildcard = $urlWildcardService->create('/articles/*', '/content/{1}', true);
        /* END: Use Case */

        $this->assertPropertiesCorrect(
            [
                'sourceUrl' => '/articles/*',
                'destinationUrl' => '/content/{1}',
                'forward' => true,
            ],
            $urlWildcard
        );
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create()
     *
     * @depends testCreate
     */
    public function testCreateThrowsInvalidArgumentExceptionOnDuplicateSourceUrl()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create a new url wildcard
        $urlWildcardService->create('/articles/*', '/content/{1}', true);

        // This call will fail with an InvalidArgumentException because the
        // sourceUrl '/articles/*' already exists.
        $urlWildcardService->create('/articles/*', '/content/data/{1}');
        /* END: Use Case */
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create()
     *
     * @depends testCreate
     */
    public function testCreateThrowsContentValidationExceptionWhenPatternsAndPlaceholdersNotMatch()
    {
        $this->expectException(ContentValidationException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // This call will fail with a ContentValidationException because the
        // number of patterns '*' does not match the number of {\d} placeholders
        $urlWildcardService->create('/articles/*', '/content/{1}/year{2}');
        /* END: Use Case */
    }

    /**
     * Test for the create() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::create()
     *
     * @depends testCreate
     */
    public function testCreateThrowsContentValidationExceptionWhenPlaceholdersNotValidNumberSequence()
    {
        $this->expectException(ContentValidationException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // This call will fail with a ContentValidationException because the
        // number of patterns '*' does not match the number of {\d} placeholders
        $urlWildcardService->create('/articles/*/*/*', '/content/{1}/year/{2}/{4}');
        /* END: Use Case */
    }

    /**
     * Test for the load() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::load()
     *
     * @depends testCreate
     */
    public function testLoad()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create a new url wildcard
        $urlWildcardId = $urlWildcardService->create('/articles/*', '/content/{1}', true)->id;

        // Load newly created url wildcard
        $urlWildcard = $urlWildcardService->load($urlWildcardId);
        /* END: Use Case */

        self::assertInstanceOf(
            URLWildcard::class,
            $urlWildcard
        );

        return $urlWildcard;
    }

    /**
     * Test for the load() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard $urlWildcard
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::load()
     *
     * @depends testLoad
     */
    public function testLoadSetsPropertiesOnURLWildcard(URLWildcard $urlWildcard)
    {
        $this->assertPropertiesCorrect(
            [
                'sourceUrl' => '/articles/*',
                'destinationUrl' => '/content/{1}',
                'forward' => true,
            ],
            $urlWildcard
        );
    }

    /**
     * Test for the load() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard $urlWildcard
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::load()
     *
     * @depends testLoad
     */
    public function testLoadThrowsNotFoundException(URLWildcard $urlWildcard)
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // This call will fail with a NotFoundException
        $urlWildcardService->load(42);
        /* END: Use Case */
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::update
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testUpdate(): void
    {
        $repository = $this->getRepository();

        $urlWildcardService = $repository->getURLWildcardService();

        $urlWildcard = $urlWildcardService->create(
            '/articles/*',
            '/content/{1}',
            true
        );

        $updateStruct = new URLWildcardUpdateStruct();
        $updateStruct->sourceUrl = '/articles/new/*';
        $updateStruct->destinationUrl = '/content/new/*';
        $updateStruct->forward = false;

        $urlWildcardService->update($urlWildcard, $updateStruct);

        $urlWildcardUpdated = $urlWildcardService->load($urlWildcard->id);

        self::assertEquals(
            [
                $urlWildcard->id,
                $updateStruct->sourceUrl,
                $updateStruct->destinationUrl,
                $updateStruct->forward,
            ],
            [
                $urlWildcardUpdated->id,
                $urlWildcardUpdated->sourceUrl,
                $urlWildcardUpdated->destinationUrl,
                $urlWildcardUpdated->forward,
            ]
        );
    }

    /**
     * Test for the remove() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::remove()
     *
     * @depends testLoad
     */
    public function testRemove()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create a new url wildcard
        $urlWildcard = $urlWildcardService->create('/articles/*', '/content/{1}', true);

        // Store wildcard url for later reuse
        $urlWildcardId = $urlWildcard->id;

        // Remove the newly created url wildcard
        $urlWildcardService->remove($urlWildcard);

        // This call will fail with a NotFoundException
        $urlWildcardService->load($urlWildcardId);
        /* END: Use Case */
    }

    /**
     * Test for the loadAll() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::loadAll()
     *
     * @depends testCreate
     */
    public function testLoadAll()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create new url wildcards
        $urlWildcardOne = $urlWildcardService->create('/articles/*', '/content/{1}', true);
        $urlWildcardTwo = $urlWildcardService->create('/news/*', '/content/{1}', true);

        // Load all available url wildcards
        $allUrlWildcards = $urlWildcardService->loadAll();
        /* END: Use Case */

        self::assertEquals(
            [
                $urlWildcardOne,
                $urlWildcardTwo,
            ],
            $allUrlWildcards
        );
    }

    /**
     * Test for the loadAll() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::loadAll()
     *
     * @depends testLoadAll
     */
    public function testLoadAllWithOffsetParameter()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create new url wildcards
        $urlWildcardOne = $urlWildcardService->create('/articles/*', '/content/{1}', true);
        $urlWildcardTwo = $urlWildcardService->create('/news/*', '/content/{1}', true);

        // Load all available url wildcards
        $allUrlWildcards = $urlWildcardService->loadAll(1);
        /* END: Use Case */

        self::assertEquals([$urlWildcardTwo], $allUrlWildcards);
    }

    /**
     * Test for the loadAll() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::loadAll()
     *
     * @depends testLoadAll
     */
    public function testLoadAllWithOffsetAndLimitParameter()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create new url wildcards
        $urlWildcardOne = $urlWildcardService->create('/articles/*', '/content/{1}');
        $urlWildcardTwo = $urlWildcardService->create('/news/*', '/content/{1}');

        // Load all available url wildcards
        $allUrlWildcards = $urlWildcardService->loadAll(0, 1);
        /* END: Use Case */

        self::assertEquals([$urlWildcardOne], $allUrlWildcards);
    }

    /**
     * Test for the loadAll() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::loadAll()
     *
     * @depends testLoadAll
     */
    public function testLoadAllReturnsEmptyArrayByDefault()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Load all available url wildcards
        $allUrlWildcards = $urlWildcardService->loadAll();
        /* END: Use Case */

        self::assertSame([], $allUrlWildcards);
    }

    /**
     * Test for the translate() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardTranslationResult
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::translate()
     *
     * @depends testCreate
     */
    public function testTranslate()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create a new url wildcard
        $urlWildcardService->create('/articles/*', '/content/{1}');

        // Translate a given url
        $result = $urlWildcardService->translate('/articles/2012/05/sindelfingen');
        /* END: Use Case */

        self::assertInstanceOf(
            URLWildcardTranslationResult::class,
            $result
        );

        return $result;
    }

    /**
     * Test for the translate() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardTranslationResult $result
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::translate()
     *
     * @depends testTranslate
     */
    public function testTranslateSetsPropertiesOnTranslationResult(URLWildcardTranslationResult $result)
    {
        $this->assertPropertiesCorrect(
            [
                'uri' => '/content/2012/05/sindelfingen',
                'forward' => false,
            ],
            $result
        );
    }

    /**
     * Test for the translate() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::translate()
     *
     * @depends testTranslate
     */
    public function testTranslateWithForwardSetToTrue()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create a new url wildcard
        $urlWildcardService->create('/articles/*/05/*', '/content/{2}/year/{1}', true);

        // Translate a given url
        $result = $urlWildcardService->translate('/articles/2012/05/sindelfingen');
        /* END: Use Case */

        $this->assertPropertiesCorrect(
            [
                'uri' => '/content/sindelfingen/year/2012',
                'forward' => true,
            ],
            $result
        );
    }

    /**
     * Test for the translate() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::translate()
     *
     * @depends testTranslate
     */
    public function testTranslateReturnsLongestMatchingWildcard()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // Create new url wildcards
        $urlWildcardService->create('/articles/*/05/*', '/content/{2}/year/{1}');
        $urlWildcardService->create('/articles/*/05/sindelfingen/*', '/content/{2}/bar/{1}');

        // Translate a given url
        $result = $urlWildcardService->translate('/articles/2012/05/sindelfingen/42');
        /* END: Use Case */

        self::assertEquals('/content/42/bar/2012', $result->uri);
    }

    /**
     * Test for the translate() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService::translate()
     *
     * @depends testTranslate
     */
    public function testTranslateThrowsNotFoundExceptionWhenNotAliasOrWildcardMatches()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $urlWildcardService = $repository->getURLWildcardService();

        // This call will fail with a NotFoundException because no wildcard or
        // url alias matches against the given url.
        $urlWildcardService->translate('/sindelfingen');
        /* END: Use Case */
    }

    public function testCountAllReturnsZeroByDefault(): void
    {
        $repository = $this->getRepository();
        $urlWildcardService = $repository->getURLWildcardService();

        self::assertSame(0, $urlWildcardService->countAll());
    }

    public function testCountAll(): void
    {
        $repository = $this->getRepository();
        $urlWildcardService = $repository->getURLWildcardService();

        $urlWildcardService->create('/articles/*', '/content/{1}');

        self::assertSame(1, $urlWildcardService->countAll());
    }
}
