<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Election;

use App\ResponseObject\Album\Pokedex;
use App\ResponseObject\Common\Pokemon;
use App\ResponseObject\Election\ElectionIndex;
use App\ResponseObject\Election\TopPokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ElectionIndex::class)]
final class ElectionIndexTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = (new Filesystem())->readFile('/app/tests/resources/integration/back/election_index.json');

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

        $this->assertSame(63, $object->getMetrics()['view_count']['sum']);
        $this->assertSame(16, $object->getMetrics()['win_count']['sum']);
        $this->assertSame(1, $object->getMetrics()['view_count']['max']);
        $this->assertSame(1, $object->getMetrics()['win_count']['max']);
        $this->assertSame(1, $object->getMetrics()['completion']['under_max_count']);
        $this->assertSame(5, $object->getMetrics()['completion']['at_max_count']);
        $this->assertSame(48, $object->getMetrics()['dex_total_count']);
        $this->assertSame(5, $object->getMetrics()['round_count']);
        $this->assertSame(3.2, $object->getMetrics()['winner_average']);
        $this->assertSame(7, $object->getMetrics()['total_round_count']);
    }

    #[Test]
    public function deserializeWithNullAndEmptyArrays(): void
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
                    "view_count": { "sum": 0, "max": 0 },
                    "win_count": { "sum": 0, "max": 0 },
                    "completion": { "under_max_count": 0, "at_max_count": 0 },
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

        $this->assertSame(0, $object->getMetrics()['view_count']['sum']);
        $this->assertSame(0, $object->getMetrics()['win_count']['sum']);
        $this->assertSame(0, $object->getMetrics()['view_count']['max']);
        $this->assertSame(0, $object->getMetrics()['win_count']['max']);
        $this->assertSame(0, $object->getMetrics()['completion']['under_max_count']);
        $this->assertSame(0, $object->getMetrics()['completion']['at_max_count']);
        $this->assertSame(0, $object->getMetrics()['dex_total_count']);
        $this->assertSame(0, $object->getMetrics()['round_count']);
        $this->assertSame(0.0, $object->getMetrics()['winner_average']);
        $this->assertSame(0, $object->getMetrics()['total_round_count']);
    }
}
