<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\UrlWildcard\Gateway;

use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard;
use Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Gateway\DoctrineDatabase;
use Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Query\CriteriaConverter;
use Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Query\CriterionHandler\MatchAll;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTest extends TestCase
{
    /**
     * Database gateway to test.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Gateway\DoctrineDatabase
     */
    protected $gateway;

    protected $fixtureData = [
        0 => [
            'id' => '1',
            'source_url' => 'developer/*',
            'destination_url' => 'dev/{1}',
            'type' => '2',
        ],
        1 => [
            'id' => '2',
            'source_url' => 'repository/*',
            'destination_url' => 'repo/{1}',
            'type' => '2',
        ],
        2 => [
            'id' => '3',
            'source_url' => 'information/*',
            'destination_url' => 'info/{1}',
            'type' => '2',
        ],
    ];

    /**
     * Test for the loadUrlWildcardData() method.
     */
    public function testLoadUrlWildcardData()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlwildcards.php');
        $gateway = $this->getGateway();

        $row = $gateway->loadUrlWildcardData(1);

        self::assertEquals(
            $this->fixtureData[0],
            $row
        );
    }

    /**
     * Test for the loadUrlWildcardsData() method.
     */
    public function testLoadUrlWildcardsData()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlwildcards.php');
        $gateway = $this->getGateway();

        $rows = $gateway->loadUrlWildcardsData();

        self::assertEquals(
            $this->fixtureData,
            $rows
        );
    }

    /**
     * Test for the loadUrlWildcardsData() method.
     */
    public function testLoadUrlWildcardsDataWithOffset()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlwildcards.php');
        $gateway = $this->getGateway();

        $row = $gateway->loadUrlWildcardsData(1);

        self::assertEquals(
            [
                0 => $this->fixtureData[1],
                1 => $this->fixtureData[2],
            ],
            $row
        );
    }

    /**
     * Test for the loadUrlWildcardsData() method.
     */
    public function testLoadUrlWildcardsDataWithOffsetAndLimit()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlwildcards.php');
        $gateway = $this->getGateway();

        $row = $gateway->loadUrlWildcardsData(1, 1);

        self::assertEquals(
            [
                0 => $this->fixtureData[1],
            ],
            $row
        );
    }

    /**
     * Test for the insertUrlWildcard() method.
     *
     * @depends testLoadUrlWildcardData
     */
    public function testInsertUrlWildcard()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlwildcards.php');
        $gateway = $this->getGateway();

        $id = $gateway->insertUrlWildcard(
            new UrlWildcard(
                [
                    'sourceUrl' => '/contact-information/*',
                    'destinationUrl' => '/contact/{1}',
                    'forward' => true,
                ]
            )
        );

        self::assertEquals(
            [
                'id' => $id,
                'source_url' => 'contact-information/*',
                'destination_url' => 'contact/{1}',
                'type' => '1',
            ],
            $gateway->loadUrlWildcardData($id)
        );
    }

    /**
     * Test for the deleteUrlWildcard() method.
     *
     * @depends testLoadUrlWildcardData
     */
    public function testDeleteUrlWildcard()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/urlwildcards.php');
        $gateway = $this->getGateway();

        $gateway->deleteUrlWildcard(1);

        self::assertEmpty($gateway->loadUrlWildcardData(1));
    }

    /**
     * Return the DoctrineDatabase gateway to test.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getGateway(): DoctrineDatabase
    {
        if (!isset($this->gateway)) {
            $criteriaConverter = new CriteriaConverter([new MatchAll()]);
            $this->gateway = new DoctrineDatabase($this->getDatabaseConnection(), $criteriaConverter);
        }

        return $this->gateway;
    }
}
