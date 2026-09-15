<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased;

use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;

#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::class, 'setMatchingConfig')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::class, 'getValues')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\RepositoryAware::class, 'setRepository')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::class, 'getRepository')]
class MultipleValuedTest extends BaseTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('matchingConfigProvider')]
    public function testSetMatchingConfig($matchingConfig)
    {
        $matcher = $this->getMultipleValuedMatcherMock();
        $matcher->setMatchingConfig($matchingConfig);
        $values = $matcher->getValues();
        self::assertIsArray($values);

        $matchingConfig = is_array($matchingConfig) ? $matchingConfig : [$matchingConfig];
        foreach ($matchingConfig as $val) {
            self::assertContains($val, $values);
        }
    }

    /**
     * Returns a set of matching values, either single or multiple.
     *
     * @return array
     */
    public static function matchingConfigProvider()
    {
        return [
            [
                'singleValue',
                ['one', 'two', 'three'],
                [123, 'nous irons au bois'],
                456,
            ],
        ];
    }

    public function testInjectRepository()
    {
        $matcher = $this->getMultipleValuedMatcherMock();
        $matcher->setRepository($this->repositoryMock);
        self::assertSame($this->repositoryMock, $matcher->getRepository());
    }

    private function getMultipleValuedMatcherMock()
    {
        return $this->getMockForAbstractClass(MultipleValued::class);
    }
}
