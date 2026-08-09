<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\EmptyContentException;
use App\Exception\InvalidJsonException;
use App\Service\RequestedContentService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(RequestedContentService::class)]
final class RequestedContentServiceTest extends TestCase
{
    #[Test]
    public function getContent(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(
                new ConstraintViolationList([])
            )
        ;

        $requestStack = new RequestStack();
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            '{"a_perfect": "json"}'
        );
        $requestStack->push($request);

        $service = new RequestedContentService($validator, $requestStack);

        $this->assertSame(
            '{"a_perfect": "json"}',
            $service->getContent(new Json()),
        );
    }

    #[Test]
    public function getContentNoRequest(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->never())
            ->method('validate')
        ;

        $requestStack = new RequestStack();

        $service = new RequestedContentService($validator, $requestStack);

        $this->expectException(EmptyContentException::class);

        $this->assertSame(
            '{"a_perfect": "json"}',
            $service->getContent(new Json()),
        );
    }

    #[Test]
    public function getContentBadRequest(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->never())
            ->method('validate')
        ;

        $requestStack = new RequestStack();
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            null,
        );
        $requestStack->push($request);

        $service = new RequestedContentService($validator, $requestStack);

        $this->expectException(EmptyContentException::class);

        $service->getContent(new Json());
    }

    #[Test]
    public function getContentBadJson(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(
                new ConstraintViolationList([
                    new ConstraintViolation(
                        'Alors en fait, non',
                        null,
                        [],
                        'douze',
                        null,
                        'what?'
                    ),
                ])
            )
        ;

        $requestStack = new RequestStack();
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            '{"a_perfect": non-json}',
        );
        $requestStack->push($request);

        $service = new RequestedContentService($validator, $requestStack);

        $this->expectException(InvalidJsonException::class);

        $service->getContent(new Json());
    }
}
