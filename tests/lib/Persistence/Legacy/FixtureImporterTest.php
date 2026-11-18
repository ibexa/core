<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy;

use Ibexa\Contracts\Core\Test\Persistence\AppendOnlyFixture;
use Ibexa\Contracts\Core\Test\Persistence\Fixture;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\FixtureImporter;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FixtureImporter::class)]
final class FixtureImporterTest extends TestCase
{
    public function testAppendOnlyFixtureKeepsTheLanguagesOfExistingContent(): void
    {
        $importer = new FixtureImporter($this->getDatabaseConnection());

        $importer->import(new class() implements Fixture {
            public function load(): array
            {
                return [
                    'ibexa_content_language' => [
                        ['id' => 2, 'disabled' => 0, 'locale' => 'eng-US', 'name' => 'English (American)'],
                        ['id' => 4, 'disabled' => 0, 'locale' => 'ger-DE', 'name' => 'German'],
                    ],
                    'ibexa_content' => [['id' => 1, 'initial_language_id' => 2, 'language_mask' => 3]],
                ];
            }
        });
        $importer->import(new class() implements AppendOnlyFixture {
            public function load(): array
            {
                return ['ibexa_content' => [['id' => 2, 'initial_language_id' => 4, 'language_mask' => 4]]];
            }
        });

        self::assertEquals(
            [[1, 2], [2, 4]],
            $this->getDatabaseConnection()->fetchAllNumeric(
                'SELECT content_id, language_id FROM ibexa_content_translation ORDER BY content_id'
            )
        );
    }
}
