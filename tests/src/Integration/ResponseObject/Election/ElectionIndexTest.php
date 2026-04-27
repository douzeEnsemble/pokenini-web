<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Election;

use App\ResponseObject\Album\Pokedex;
use App\ResponseObject\Common\Pokemon;
use App\ResponseObject\Election\ElectionIndex;
use App\ResponseObject\Election\TopPokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ElectionIndex::class)]
final class ElectionIndexTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = (string) file_get_contents('/app/tests/resources/integration/back/election_index.json');

        $object = $serializer->deserialize($json, ElectionIndex::class, 'json');

        $this->assertSame('pick', $object->getType());
        $this->assertCount(12, $object->getPokemons());
        $this->assertContainsOnlyInstancesOf(Pokemon::class, $object->getPokemons());
        $this->assertInstanceOf(Pokedex::class, $object->getPokedex());
        $this->assertCount(5, $object->getElectionTop());
        $this->assertContainsOnlyInstancesOf(TopPokemon::class, $object->getElectionTop());
        $this->assertSame(0, $object->getDetachedCount());
        $this->assertFalse($object->isTheLastOne());
        $this->assertFalse($object->isTheLastPage());

        $this->assertSame(63, $object->getMetrics()['view_count_sum']);
        $this->assertSame(16, $object->getMetrics()['win_count_sum']);
        $this->assertSame(1, $object->getMetrics()['view_count_max']);
        $this->assertSame(1, $object->getMetrics()['win_count_max']);
        $this->assertSame(1, $object->getMetrics()['under_max_view_count']);
        $this->assertSame(5, $object->getMetrics()['max_view_count']);
        $this->assertSame(48, $object->getMetrics()['dex_total_count']);
        $this->assertSame(5, $object->getMetrics()['round_count']);
        $this->assertSame(3.2, $object->getMetrics()['winner_average']);
        $this->assertSame(7, $object->getMetrics()['total_round_count']);
    }

    public function testDeserializeWithNullAndEmptyArrays(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "type": "pick",
                "pokemons": [],
                "pokedex": null,
                "election_top": [],
                "metrics": {
                    "view_count_sum": 0,
                    "win_count_sum": 0,
                    "view_count_max": 0,
                    "win_count_max": 0,
                    "under_max_view_count": 0,
                    "max_view_count": 0,
                    "dex_total_count": 0,
                    "round_count": 0,
                    "winner_average": 0.0,
                    "total_round_count": 0
                },
                "detached_count": 1,
                "is_the_last_one": true,
                "is_the_last_page": true
            }
            JSON;

        $object = $serializer->deserialize($json, ElectionIndex::class, 'json');

        $this->assertSame('pick', $object->getType());
        $this->assertCount(0, $object->getPokemons());
        $this->assertNull($object->getPokedex());
        $this->assertCount(0, $object->getElectionTop());
        $this->assertSame(1, $object->getDetachedCount());
        $this->assertTrue($object->isTheLastOne());
        $this->assertTrue($object->isTheLastPage());

        $this->assertSame(0, $object->getMetrics()['view_count_sum']);
        $this->assertSame(0, $object->getMetrics()['win_count_sum']);
        $this->assertSame(0, $object->getMetrics()['view_count_max']);
        $this->assertSame(0, $object->getMetrics()['win_count_max']);
        $this->assertSame(0, $object->getMetrics()['under_max_view_count']);
        $this->assertSame(0, $object->getMetrics()['max_view_count']);
        $this->assertSame(0, $object->getMetrics()['dex_total_count']);
        $this->assertSame(0, $object->getMetrics()['round_count']);
        $this->assertSame(0.0, $object->getMetrics()['winner_average']);
        $this->assertSame(0, $object->getMetrics()['total_round_count']);
    }
}
