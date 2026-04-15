<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Election\TopPokemon;
use App\Service\Back\GetElectionTopService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(GetElectionTopService::class)]
class GetElectionTopServiceTest extends TestCase
{
    use BackServiceTrait;
    use ResponseObjectTrait;

    public function testGet(): void
    {
        $json = (string) file_get_contents('/app/tests/resources/unit/service/back/election_top_5_home_fav.json');

        $items = [
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
        ];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                TopPokemon::class.'[]',
                'json',
            )
            ->willReturn($items)
        ;

        $electionTop = $this->getService('home', 'fav', 5, $serializer)->getTop('home', 'fav', 5);

        $this->assertCount(5, $electionTop->getItems());
    }

    public function testWithoutLoggedUser(): void
    {
        $json = (string) file_get_contents('/app/tests/resources/unit/service/back/election_top_5_home_fav.json');

        $items = [
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
        ];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                TopPokemon::class.'[]',
                'json',
            )
            ->willReturn($items)
        ;

        /** @var GetElectionTopService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetElectionTopService::class,
            'GET',
            $json,
            'election/top?dex_slug=home&election_slug=fav&count=5',
            [],
            $serializer,
        );

        $electionTop = $service->getTop('home', 'fav', 5);

        $this->assertCount(5, $electionTop->getItems());
    }

    private function getService(
        string $dexSlug,
        string $electionSlug,
        int $count,
        ?SerializerInterface $serializer,
    ): GetElectionTopService {
        $filename = "/app/tests/resources/unit/service/back/election_top_{$count}_{$dexSlug}_{$electionSlug}.json";

        /** @var GetElectionTopService */
        return $this->getServiceWithLoggedUser(
            GetElectionTopService::class,
            'GET',
            (string) file_get_contents($filename),
            "election/top?dex_slug={$dexSlug}&election_slug={$electionSlug}&count={$count}",
            [],
            $serializer,
        );
    }
}
