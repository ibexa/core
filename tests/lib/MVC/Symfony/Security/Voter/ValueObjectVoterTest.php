<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Security\Voter;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\MVC\Symfony\Controller\Content\ViewController;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Voter\ValueObjectVoter;
use Ibexa\Core\Repository\Permission\PermissionResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ValueObjectVoterTest extends TestCase
{
    private PermissionResolver & MockObject $permissionResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionResolver = $this->createMock(PermissionResolver::class);
    }

    /**
     * @dataProvider supportsAttributeProvider
     */
    public function testSupportsAttribute(mixed $attribute, bool $expectedResult): void
    {
        $voter = new ValueObjectVoter($this->permissionResolver);
        self::assertSame($expectedResult, $voter->supportsAttribute($attribute));
    }

    public function supportsAttributeProvider(): array
    {
        return [
            ['foo', false],
            [new Attribute('foo', 'bar'), false],
            [new Attribute('foo', 'bar', ['some' => 'thing']), false],
            [new \stdClass(), false],
            [['foo'], false],
            [
                new Attribute(
                    'foo',
                    'bar',
                    ['valueObject' => $this->getMockForAbstractClass(ValueObject::class)]
                ),
                true,
            ],
        ];
    }

    /**
     * @dataProvider supportsClassProvider
     */
    public function testSupportsClass(string $class): void
    {
        $voter = new ValueObjectVoter($this->permissionResolver);
        self::assertTrue($voter->supportsClass($class));
    }

    public function supportsClassProvider(): array
    {
        return [
            ['foo'],
            ['bar'],
            [ValueObject::class],
            [ViewController::class],
        ];
    }

    /**
     * @dataProvider voteInvalidAttributeProvider
     */
    public function testVoteInvalidAttribute(array $attributes): void
    {
        $voter = new ValueObjectVoter($this->permissionResolver);
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->createMock(TokenInterface::class),
                new \stdClass(),
                $attributes
            )
        );
    }

    public function voteInvalidAttributeProvider(): array
    {
        return [
            [[]],
            [['foo']],
            [['foo', 'bar', ['some' => 'thing']]],
            [[new \stdClass()]],
            [[new Attribute('content', 'read')]],
        ];
    }

    /**
     * @dataProvider voteProvider
     */
    public function testVote(Attribute $attribute, bool $repositoryCanUser, int $expectedResult): void
    {
        $voter = new ValueObjectVoter($this->permissionResolver);
        $targets = isset($attribute->limitations['targets']) ? $attribute->limitations['targets'] : [];
        $this->permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->with($attribute->module, $attribute->function, $attribute->limitations['valueObject'], $targets)
            ->will(self::returnValue($repositoryCanUser));

        self::assertSame(
            $expectedResult,
            $voter->vote(
                $this->createMock(TokenInterface::class),
                new \stdClass(),
                [$attribute]
            )
        );
    }

    public function voteProvider(): array
    {
        return [
            [
                new Attribute('content', 'read', [
                    'valueObject' => $this->getMockForAbstractClass(
                        ValueObject::class
                    ),
                ]),
                true,
                VoterInterface::ACCESS_GRANTED,
            ],
            [
                new Attribute('content', 'read', [
                    'valueObject' => $this->getMockForAbstractClass(
                        ValueObject::class
                    ),
                ]),
                false,
                VoterInterface::ACCESS_DENIED,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => $this->getMockForAbstractClass(ValueObject::class),
                        'targets' => [$this->getMockForAbstractClass(ValueObject::class)],
                    ]
                ),
                true,
                VoterInterface::ACCESS_GRANTED,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => $this->getMockForAbstractClass(ValueObject::class),
                        'targets' => [$this->getMockForAbstractClass(ValueObject::class)],
                    ]
                ),
                true,
                VoterInterface::ACCESS_GRANTED,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => $this->getMockForAbstractClass(ValueObject::class),
                        'targets' => [$this->getMockForAbstractClass(ValueObject::class)],
                    ]
                ),
                false,
                VoterInterface::ACCESS_DENIED,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => $this->getMockForAbstractClass(ValueObject::class),
                        'targets' => [$this->getMockForAbstractClass(ValueObject::class)],
                    ]
                ),
                false,
                VoterInterface::ACCESS_DENIED,
            ],
        ];
    }
}
