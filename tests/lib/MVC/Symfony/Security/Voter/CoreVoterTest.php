<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Security\Voter;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\MVC\Symfony\Controller\Content\ViewController;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Voter\CoreVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CoreVoterTest extends TestCase
{
    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $permissionResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionResolver = $this->createMock(PermissionResolver::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportsAttributeProvider')]
    public function testSupportsAttribute($attribute, $expectedResult)
    {
        $voter = new CoreVoter($this->permissionResolver);
        self::assertSame($expectedResult, $voter->supportsAttribute($attribute));
    }

    public static function supportsAttributeProvider()
    {
        return [
            ['foo', false],
            [new Attribute('foo', 'bar'), true],
            [new Attribute('foo', 'bar', ['some' => 'thing']), false],
            [new \stdClass(), false],
            [['foo'], false],
            [
                new Attribute(
                    'foo',
                    'bar',
                    ['valueObject' => self::createStub(ValueObject::class)]
                ),
                false,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportsClassProvider')]
    public function testSupportsClass($class)
    {
        $voter = new CoreVoter($this->permissionResolver);
        self::assertTrue($voter->supportsClass($class));
    }

    public static function supportsClassProvider()
    {
        return [
            ['foo'],
            ['bar'],
            [ValueObject::class],
            [ViewController::class],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('voteInvalidAttributeProvider')]
    public function testVoteInvalidAttribute(array $attributes)
    {
        $voter = new CoreVoter($this->permissionResolver);
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->createStub(TokenInterface::class),
                new \stdClass(),
                $attributes
            )
        );
    }

    public static function voteInvalidAttributeProvider()
    {
        return [
            [[]],
            [['foo']],
            [['foo', 'bar', ['some' => 'thing']]],
            [[new \stdClass()]],
            [
                [
                    new Attribute(
                        'foo',
                        'bar',
                        ['valueObject' => self::createStub(ValueObject::class)]
                    ),
                ],
                false,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('voteProvider')]
    public function testVote(Attribute $attribute, $repositoryCanUser, $expectedResult)
    {
        $voter = new CoreVoter($this->permissionResolver);
        if ($repositoryCanUser !== null) {
            $this->permissionResolver
                ->expects(self::once())
                ->method('hasAccess')
                ->with($attribute->module, $attribute->function)
                ->will(self::returnValue($repositoryCanUser));
        } else {
            $this->permissionResolver
                ->expects(self::never())
                ->method('hasAccess');
        }

        self::assertSame(
            $expectedResult,
            $voter->vote(
                $this->createStub(TokenInterface::class),
                new \stdClass(),
                [$attribute]
            )
        );
    }

    public static function voteProvider()
    {
        return [
            [
                new Attribute('content', 'read'),
                true,
                VoterInterface::ACCESS_GRANTED,
            ],
            [
                new Attribute('foo', 'bar'),
                true,
                VoterInterface::ACCESS_GRANTED,
            ],
            [
                new Attribute('content', 'read'),
                false,
                VoterInterface::ACCESS_DENIED,
            ],
            [
                new Attribute('some', 'thing'),
                false,
                VoterInterface::ACCESS_DENIED,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => self::createStub(ValueObject::class),
                        'targets' => self::createStub(ValueObject::class),
                    ]
                ),
                null,
                VoterInterface::ACCESS_ABSTAIN,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => self::createStub(ValueObject::class),
                        'targets' => [self::createStub(ValueObject::class)],
                    ]
                ),
                null,
                VoterInterface::ACCESS_ABSTAIN,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => self::createStub(ValueObject::class),
                        'targets' => self::createStub(ValueObject::class),
                    ]
                ),
                null,
                VoterInterface::ACCESS_ABSTAIN,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => self::createStub(ValueObject::class),
                        'targets' => [self::createStub(ValueObject::class)],
                    ]
                ),
                null,
                VoterInterface::ACCESS_ABSTAIN,
            ],
        ];
    }
}
