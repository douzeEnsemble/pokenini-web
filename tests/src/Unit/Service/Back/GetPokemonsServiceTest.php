<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Election\ElectionList;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetPokemonsService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetPokemonsService::class)]
final class GetPokemonsServiceTest extends AbstractTestBackService
{
    use ResponseObjectTrait;

    public function testGet(): void
    {
        $electionList = $this
            ->getService(
                '123',
                '',
                3,
            )
            ->get(
                '123',
                '',
                3,
                [],
            )
        ;

        $this->assertCount(3, $electionList->getItems());
    }

    public function testGetWithFilters(): void
    {
        $electionList = $this
            ->getService(
                '123',
                '',
                5,
                '_cflegendary',
                [
                    'cf' => ['legendary'],
                ],
            )
            ->get(
                '123',
                '',
                5,
                [
                    'cf' => ['legendary'],
                ],
            )
        ;

        $this->assertSame('vote', $electionList->getType());

        $this->assertCount(3, $electionList->getItems());
    }

    public function testGetWithoutLoggedUser(): void
    {
        $dir = '/app/tests/resources/unit/service/back';
        $filename = "{$dir}/pokemons_123__3.json";
        $json = (new Filesystem())->readFile($filename);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                ElectionList::class,
                'json',
            )
            ->willReturn($this->getStubElectionList())
        ;

        /** @var GetPokemonsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            $json,
            'pokemons/to_choose',
            [
                'query' => [
                    'dex_slug' => '123',
                    'election_slug' => '',
                    'count' => 3,
                ],
            ],
            $serializer,
        );

        $electionList = $service->get('123', '', 3, []);

        $this->assertSame('vote', $electionList->getType());

        $this->assertCount(3, $electionList->getItems());
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
        return new GetPokemonsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    /**
     * @param array<string, array<int, string>|string> $filters
     */
    private function getService(
        string $dexSlug,
        string $electionSlug,
        int $count,
        string $filtersStr = '',
        array $filters = [],
    ): GetPokemonsService {
        $dir = '/app/tests/resources/unit/service/back';
        $filename = "{$dir}/pokemons_{$dexSlug}_{$electionSlug}_{$count}{$filtersStr}.json";
        $json = (new Filesystem())->readFile($filename);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                ElectionList::class,
                'json',
            )
            ->willReturn($this->getStubElectionList())
        ;

        $options = [
            'query' => array_merge(
                [
                    'dex_slug' => $dexSlug,
                    'election_slug' => $electionSlug,
                    'count' => "{$count}",
                ],
                $filters,
            ),
        ];

        /** @var GetPokemonsService */
        return $this->getServiceWithLoggedUser(
            'GET',
            $json,
            'pokemons/to_choose',
            $options,
            $serializer,
        );
    }
}
