<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenService;
use App\Service\Back\ModifyAlbumService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
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
                'album/home/pikachu',
                'yes',
            )
            ->modify(
                'PATCH',
                'home',
                'pikachu',
                'yes',
            )
        ;
    }

    public function testModifyPut(): void
    {
        $this
            ->getService(
                'PUT',
                'album/home/pikachu',
                'yes',
            )
            ->modify(
                'PUT',
                'home',
                'pikachu',
                'yes',
            )
        ;
    }

    public function testModifyPost(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $client = $this->createMock(HttpClientInterface::class);

        $userTokenService = $this->createMock(UserTokenService::class);

        $serializer = $this->createMock(SerializerInterface::class);

        $service = new ModifyAlbumService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            $serializer,
        );

        $this->expectException(\InvalidArgumentException::class);

        $service->modify(
            'POST',
            'home',
            'pikachu',
            'yes',
        );
    }

    public function testModifyPatchWithoutLoggedUser(): void
    {
        /** @var ModifyAlbumService $service */
        $service = $this->getServiceWithoutLoggedUser(
            ModifyAlbumService::class,
            'PATCH',
            '',
            'album/home/pikachu',
            [
                'body' => 'yes',
            ],
        );

        $service->modify('PATCH', 'home', 'pikachu', 'yes');
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
