<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Album\Album;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetPokedexService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetPokedexService::class)]
final class GetPokedexServiceTest extends AbstractTestBackService
{
    use ResponseObjectTrait;

    #[Test]
    public function get(): void
    {
        $json = '{"doesnt": "matter"}';

        $album = $this->getStubAlbum();

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                Album::class,
                'json',
            )
            ->willReturn($album)
        ;

        $service = $this->getService(
            'lite',
            $json,
            [
                'catch_states' => [
                    'yes',
                ],
            ],
            $serializer,
        );

        $album = $service->get(
            'lite',
            [
                'catch_states' => [
                    'yes',
                ],
            ],
        );

        $this->assertSame('Stub', $album->getPokedex()->getDex()?->getName());
        $this->assertCount(1, $album->getPokedex()->getPokemons());
        $this->assertSame(2, $album->getPokedex()->getReport()->getTotalCaught());
        $this->assertSame(0, $album->getPokedex()->getFilteredReport()->getTotalCaught());
    }

    #[Test]
    public function getWithTrainerId(): void
    {
        $json = '{"doesnt": "matter"}';

        $album = $this->getStubAlbum();

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                Album::class,
                'json',
            )
            ->willReturn($album)
        ;

        $service = $this->getService(
            'lite',
            $json,
            [
                'trainer_id' => '123',
                'catch_states' => [
                    'yes',
                ],
            ],
            $serializer,
        );

        $album = $service->getWithTrainerId(
            '123',
            'lite',
            [
                'catch_states' => [
                    'yes',
                ],
            ],
        );

        $this->assertSame('Stub', $album->getPokedex()->getDex()?->getName());
        $this->assertCount(1, $album->getPokedex()->getPokemons());
        $this->assertSame(2, $album->getPokedex()->getReport()->getTotalCaught());
        $this->assertSame(0, $album->getPokedex()->getFilteredReport()->getTotalCaught());
    }

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return new GetPokedexService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    /**
     * @param string[]|string[][]|string[][][] $queryParams
     */
    private function getService(
        string $dexSlug,
        string $json,
        array $queryParams,
        ?SerializerInterface $serializer = null,
    ): GetPokedexService {
        /** @var GetPokedexService */
        return $this->getServiceWithLoggedUser(
            'GET',
            $json,
            "album/{$dexSlug}",
            [
                'query' => $queryParams,
            ],
            $serializer,
        );
    }
}
