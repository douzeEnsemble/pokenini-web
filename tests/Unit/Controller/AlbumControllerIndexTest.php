<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AlbumController;
use App\Security\UserTokenService;
use App\Service\ApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AlbumControllerIndexTest extends TestCase
{
    public function testIndexApiException(): void
    {
        $apiService = $this->createMock(ApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getPokedex')
            ->willThrowException(
                new TransportException('Whoops!')
            )
            ->with(
                'douze',
                '121212',
            )
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('1234567890')
        ;

        $validator = $this->createMock(ValidatorInterface::class);

        $controller = new AlbumController(
            $apiService,
            $userTokenService,
            $validator,
        );

        $request = $this->createMock(Request::class);
        $request->query = new InputBag(['t' => '121212']);

        $this->expectException(NotFoundHttpException::class);

        $controller->index($request, 'douze');
    }
}
