<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Album\Album;
use App\Service\Back\GetPokedexService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(GetPokedexService::class)]
class GetPokedexServiceTest extends TestCase
{
    use BackServiceTrait;
    use ResponseObjectTrait;

    public function testGet(): void
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

        $this->assertNotNull($album);
        $this->assertSame('Stub', $album->getDex()?->getName());
        $this->assertCount(1, $album->getPokemons());
        $this->assertSame(2, $album->getReport()->getTotalCaught());
        $this->assertSame(0, $album->getFilteredReport()->getTotalCaught());
    }

    public function testGetWithTrainerId(): void
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

        $this->assertNotNull($album);
        $this->assertSame('Stub', $album->getDex()?->getName());
        $this->assertCount(1, $album->getPokemons());
        $this->assertSame(2, $album->getReport()->getTotalCaught());
        $this->assertSame(0, $album->getFilteredReport()->getTotalCaught());
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
            GetPokedexService::class,
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
