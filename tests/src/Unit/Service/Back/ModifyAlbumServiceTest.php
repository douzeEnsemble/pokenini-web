<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenService;
use App\Service\Back\ModifyAlbumService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(ModifyAlbumService::class)]
class ModifyAlbumServiceTest extends TestCase
{
    use BackServiceTrait;

    public function testModifyPatch(): void
    {
        $this
            ->getService(
                'PATCH',
                'album/123/home/pikachu',
                'yes',
            )
            ->modify(
                'PATCH',
                'home',
                'pikachu',
                'yes',
                '123',
            )
        ;
    }

    public function testModifyPut(): void
    {
        $this
            ->getService(
                'PUT',
                'album/123/home/pikachu',
                'yes',
            )
            ->modify(
                'PUT',
                'home',
                'pikachu',
                'yes',
                '123',
            )
        ;
    }

    public function testModifyPost(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $client = $this->createMock(HttpClientInterface::class);

        $userTokenService = $this->createMock(UserTokenService::class);

        $service = new ModifyAlbumService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
        );

        $this->expectException(\InvalidArgumentException::class);

        $service->modify(
            'POST',
            'home',
            'pikachu',
            'yes',
            '123',
        );
    }

    public function testModifyPatchWithoutLoggedUser(): void
    {
        /** @var ModifyAlbumService $service */
        $service = $this->getServiceWithoutLoggedUser(
            ModifyAlbumService::class,
            'PATCH',
            '',
            'album/123/home/pikachu',
            [
                'body' => 'yes',
            ],
        );

        $service->modify('PATCH', 'home', 'pikachu', 'yes', '123');
    }

    private function getService(
        string $method,
        string $suffix,
        string $body
    ): ModifyAlbumService {
        /** @var ModifyAlbumService */
        return $this->getServiceWithLoggedUser(
            ModifyAlbumService::class,
            $method,
            '',
            $suffix,
            [
                'body' => $body,
            ],
        );
    }
}
