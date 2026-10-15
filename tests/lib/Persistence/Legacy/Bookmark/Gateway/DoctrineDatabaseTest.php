<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Bookmark\Gateway;

use Doctrine\DBAL\Exception;
use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Bookmark\Gateway
 */
class DoctrineDatabaseTest extends TestCase
{
    public const EXISTING_BOOKMARK_ID = 1;
    public const EXISTING_BOOKMARK_DATA = [
        'id' => 1,
        'name' => 'Lorem ipsum dolor',
        'node_id' => 5,
        'user_id' => 14,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(__DIR__ . '/../_fixtures/bookmarks.php');
    }

    public function testInsertBookmark()
    {
        $id = $this->getGateway()->insertBookmark(new Bookmark([
            'userId' => 14,
            'locationId' => 54,
        ]));

        $data = $this->loadBookmark($id);

        self::assertEquals([
            'id' => $id,
            'name' => '',
            'node_id' => '54',
            'user_id' => '14',
        ], $data);
    }

    public function testDeleteBookmark()
    {
        $this->getGateway()->deleteBookmark(self::EXISTING_BOOKMARK_ID);

        self::assertEmpty($this->loadBookmark(self::EXISTING_BOOKMARK_ID));
    }

    public function testLoadBookmarkDataById()
    {
        self::assertEquals(
            [self::EXISTING_BOOKMARK_DATA],
            $this->getGateway()->loadBookmarkDataById(self::EXISTING_BOOKMARK_ID)
        );
    }

    public function testLoadBookmarkDataByUserIdAndLocationId()
    {
        $data = $this->getGateway()->loadBookmarkDataByUserIdAndLocationId(
            (int) self::EXISTING_BOOKMARK_DATA['user_id'],
            [(int) self::EXISTING_BOOKMARK_DATA['node_id']]
        );

        self::assertEquals([self::EXISTING_BOOKMARK_DATA], $data);
    }

    /**
     * @dataProvider dataProviderForLoadUserBookmarks
     */
    public function testLoadUserBookmarks(
        int $userId,
        int $offset,
        int $limit,
        array $expected
    ) {
        self::assertEquals($expected, $this->getGateway()->loadUserBookmarks($userId, $offset, $limit));
    }

    /**
     * @dataProvider dataProviderForLoadUserBookmarks
     */
    public function testCountUserBookmarks(
        int $userId,
        int $offset,
        int $limit,
        array $expected
    ) {
        self::assertEquals(count($expected), $this->getGateway()->countUserBookmarks($userId));
    }

    public function dataProviderForLoadUserBookmarks(): array
    {
        $fixtures = (require __DIR__ . '/../_fixtures/bookmarks.php')[DoctrineDatabase::TABLE_BOOKMARKS];

        $expectedRows = static function ($userId) use ($fixtures) {
            $rows = array_filter($fixtures, static function (array $row) use ($userId): bool {
                return $row['user_id'] == $userId;
            });

            usort($rows, static function (
                $a,
                $b
            ): int {
                return $b['id'] <=> $a['id'];
            });

            return $rows;
        };

        $userId = self::EXISTING_BOOKMARK_DATA['user_id'];

        return [
            [
                $userId, 0, 10, $expectedRows($userId),
            ],
        ];
    }

    public function testLocationSwapped()
    {
        $bookmark1Id = 3;
        $bookmark2Id = 4;

        $bookmark1BeforeSwap = $this->loadBookmark($bookmark1Id);
        $bookmark2BeforeSwap = $this->loadBookmark($bookmark2Id);

        $this->getGateway()->locationSwapped(
            (int) $bookmark1BeforeSwap['node_id'],
            (int) $bookmark2BeforeSwap['node_id']
        );

        $bookmark1AfterSwap = $this->loadBookmark($bookmark1Id);
        $bookmark2AfterSwap = $this->loadBookmark($bookmark2Id);

        self::assertEquals($bookmark1BeforeSwap['node_id'], $bookmark2AfterSwap['node_id']);
        self::assertEquals($bookmark2BeforeSwap['node_id'], $bookmark1AfterSwap['node_id']);
    }

    /**
     * Return a ready to test DoctrineStorage gateway.
     *
     * @throws Exception
     */
    protected function getGateway(): Gateway
    {
        return new DoctrineDatabase($this->getDatabaseConnection());
    }

    private function loadBookmark(int $id): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id', 'name', 'node_id', 'user_id')
            ->from(DoctrineDatabase::TABLE_BOOKMARKS)
            ->where('id = :id')
            ->setParameter('id', $id);

        $data = $this->connection->executeQuery($qb->getSQL(), $qb->getParameters())->fetchAssociative();

        return is_array($data) ? $data : [];
    }
}
