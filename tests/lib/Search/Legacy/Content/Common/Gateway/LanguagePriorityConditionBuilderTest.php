<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Legacy\Content\Common\Gateway;

use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\LanguagePriorityConditionBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageHandlerMock;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LanguagePriorityConditionBuilder::class)]
final class LanguagePriorityConditionBuilderTest extends TestCase
{
    public function testLoadLanguageIdsKeepsTheOrderOfTheCodes(): void
    {
        self::assertSame([8, 2, 4], $this->createBuilder()->loadLanguageIds(['ger-DE', 'eng-US', 'eng-GB']));
    }

    public function testLoadLanguageIdsThrowsOnAMissingLanguage(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('fre-FR');

        $this->createBuilder()->loadLanguageIds(['eng-US', 'fre-FR']);
    }

    private function createBuilder(): LanguagePriorityConditionBuilder
    {
        return new LanguagePriorityConditionBuilder($this->getDatabaseConnection(), new LanguageHandlerMock());
    }
}
