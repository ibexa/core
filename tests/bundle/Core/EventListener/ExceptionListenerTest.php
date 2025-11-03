<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Bundle\Core\EventListener;

use Exception;
use Ibexa\Bundle\Core\EventListener\ExceptionListener;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\Base\Exceptions\ContentTypeFieldDefinitionValidationException;
use Ibexa\Core\Base\Exceptions\ContentTypeValidationException;
use Ibexa\Core\Base\Exceptions\ContentValidationException;
use Ibexa\Core\Base\Exceptions\ForbiddenException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\LimitationValidationException;
use Ibexa\Core\Base\Exceptions\MissingClass;
use Ibexa\Core\Base\Exceptions\NotFound\FieldTypeNotFoundException;
use Ibexa\Core\Base\Exceptions\NotFound\LimitationNotFoundException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionListenerTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Contracts\Translation\TranslatorInterface */
    private TranslatorInterface $translator;

    private ExceptionListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->listener = new ExceptionListener($this->translator);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::EXCEPTION => ['onKernelException', 10]],
            ExceptionListener::getSubscribedEvents()
        );
    }

    /**
     * @param \Exception $exception
     *
     * @return \Symfony\Component\HttpKernel\Event\ExceptionEvent
     */
    private function generateExceptionEvent(Exception $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    public function testNotFoundException(): void
    {
        $messageTemplate = 'some message template';
        $translationParams = ['some' => 'thing'];
        $exception = new NotFoundException('foo', 'bar');
        $exception->setMessageTemplate($messageTemplate);
        $exception->setParameters($translationParams);
        $event = $this->generateExceptionEvent($exception);

        $translatedMessage = 'translated message';
        $this->mockTranslatorTrans($messageTemplate, $translationParams, $translatedMessage);

        $this->assertExceptionSame(
            NotFoundHttpException::class,
            $event,
            $exception,
            $translatedMessage
        );
    }

    public function testUnauthorizedException(): void
    {
        $messageTemplate = 'some message template';
        $translationParams = ['some' => 'thing'];
        $exception = new UnauthorizedException('foo', 'bar');
        $exception->setMessageTemplate($messageTemplate);
        $exception->setParameters($translationParams);
        $event = $this->generateExceptionEvent($exception);

        $translatedMessage = 'translated message';
        $this->mockTranslatorTrans($messageTemplate, $translationParams, $translatedMessage);

        $this->assertExceptionSame(
            AccessDeniedException::class,
            $event,
            $exception,
            $translatedMessage
        );
    }

    /**
     * @dataProvider badRequestExceptionProvider
     *
     * @param \Exception&\Ibexa\Core\Base\Translatable $exception
     */
    public function testBadRequestException($exception): void
    {
        $messageTemplate = 'some message template';
        $translationParams = ['some' => 'thing'];
        $exception->setMessageTemplate($messageTemplate);
        $exception->setParameters($translationParams);
        $event = $this->generateExceptionEvent($exception);

        $translatedMessage = 'translated message';
        $this->mockTranslatorTrans($messageTemplate, $translationParams, $translatedMessage);

        $this->assertExceptionSame(
            BadRequestHttpException::class,
            $event,
            $exception,
            $translatedMessage
        );
    }

    /**
     * @dataProvider provideDataForTestForbiddenException
     *
     * @param \Exception&\Ibexa\Core\Base\Translatable $exception
     */
    public function testForbiddenException($exception): void
    {
        $messageTemplate = 'some message template';
        $translationParams = ['some' => 'thing'];
        $exception->setMessageTemplate($messageTemplate);
        $exception->setParameters($translationParams);
        $event = $this->generateExceptionEvent($exception);

        $translatedMessage = 'translated message';
        $this->mockTranslatorTrans($messageTemplate, $translationParams, $translatedMessage);

        $this->assertExceptionSame(
            AccessDeniedHttpException::class,
            $event,
            $exception,
            $translatedMessage
        );
    }

    public function testUntouchedException(): void
    {
        $exception = new \RuntimeException('foo');
        $event = $this->generateExceptionEvent($exception);
        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->listener->onKernelException($event);
        self::assertSame($exception, $event->getThrowable());
    }

    /**
     * @dataProvider otherExceptionProvider
     *
     * @param \Exception&\Ibexa\Core\Base\Translatable $exception
     */
    public function testOtherRepositoryException(Exception $exception): void
    {
        $messageTemplate = 'some message template';
        $translationParams = ['some' => 'thing'];
        $exception->setMessageTemplate($messageTemplate);
        $exception->setParameters($translationParams);
        $event = $this->generateExceptionEvent($exception);

        $translatedMessage = 'translated message';
        $this->mockTranslatorTrans($messageTemplate, $translationParams, $translatedMessage);

        $this->listener->onKernelException($event);
        $convertedException = $event->getThrowable();
        self::assertInstanceOf(HttpException::class, $convertedException);
        self::assertSame($exception, $convertedException->getPrevious());
        self::assertSame(get_class($exception) . ': ' . $translatedMessage, $convertedException->getMessage());
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $convertedException->getStatusCode());
    }

    /**
     * @return iterable<array{\Exception&\Ibexa\Core\Base\Translatable}>
     */
    public function badRequestExceptionProvider(): iterable
    {
        return [
            [new BadStateException('foo', 'bar')],
            [new InvalidArgumentException('foo', 'bar')],
            [new InvalidArgumentType('foo', 'bar')],
            [new InvalidArgumentValue('foo', 'bar')],
        ];
    }

    /**
     * @return iterable<array{\Exception&\Ibexa\Core\Base\Translatable}>
     */
    public function provideDataForTestForbiddenException(): iterable
    {
        return [
            [new ForbiddenException('foo "%param%"', ['%param%' => 'bar'])],
            [new LimitationValidationException([])],
            [new ContentValidationException('foo')],
            [new ContentTypeValidationException('foo')],
            [new ContentFieldValidationException([])],
            [new ContentTypeFieldDefinitionValidationException([])],
        ];
    }

    /**
     * @return iterable<array{\Exception&\Ibexa\Core\Base\Translatable}>
     */
    public function otherExceptionProvider(): iterable
    {
        return [
            [new MissingClass('foo')],
            [new FieldTypeNotFoundException('foo')],
            [new LimitationNotFoundException('foo')],
        ];
    }

    /**
     * @param array<string, mixed> $translationParams
     */
    private function mockTranslatorTrans(
        string $messageTemplate,
        array $translationParams,
        string $translatedMessage
    ): void {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with($messageTemplate, $translationParams)
            ->willReturn($translatedMessage);
    }

    /**
     * @param class-string $expectedException
     * @param \Exception&\Ibexa\Core\Base\Translatable $exception
     */
    private function assertExceptionSame(
        string $expectedException,
        ExceptionEvent $event,
        $exception,
        string $translatedMessage
    ): void {
        $this->listener->onKernelException($event);
        $convertedException = $event->getThrowable();

        self::assertInstanceOf($expectedException, $convertedException);
        self::assertSame($exception, $convertedException->getPrevious());
        self::assertSame($translatedMessage, $convertedException->getMessage());
    }
}

class_alias(ExceptionListenerTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\EventListener\ExceptionListenerTest');
