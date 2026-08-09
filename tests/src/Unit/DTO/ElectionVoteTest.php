<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionVote;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * @internal
 */
#[CoversClass(ElectionVote::class)]
final class ElectionVoteTest extends TestCase
{
    #[Test]
    public function propertiesAreReadonly(): void
    {
        $this->assertTrue((new \ReflectionProperty(ElectionVote::class, 'dexSlug'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionVote::class, 'electionSlug'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionVote::class, 'winnersSlugs'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionVote::class, 'losersSlugs'))->isReadOnly());
    }

    #[Test]
    public function ok(): void
    {
        $object = ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'election_slug' => 'douze',
            'winners_slugs' => ['pikachu'],
            'losers_slugs' => ['pichu', 'raichu'],
        ]);

        $this->assertSame('pokedex', $object->dexSlug);
        $this->assertSame('douze', $object->electionSlug);
        $this->assertSame(['pikachu'], $object->winnersSlugs);
        $this->assertSame(['pichu', 'raichu'], $object->losersSlugs);
    }

    #[Test]
    public function winnerAsLoser(): void
    {
        $object = ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'election_slug' => 'douze',
            'winners_slugs' => ['pikachu'],
            'losers_slugs' => ['pichu', 'pikachu', 'raichu'],
        ]);

        $this->assertSame('pokedex', $object->dexSlug);
        $this->assertSame('douze', $object->electionSlug);
        $this->assertSame(['pikachu'], $object->winnersSlugs);
        $this->assertSame(['pichu', 'raichu'], $object->losersSlugs);
    }

    #[Test]
    public function winnersAsLosers(): void
    {
        $object = ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'election_slug' => 'douze',
            'winners_slugs' => ['pikachu', 'pichu'],
            'losers_slugs' => ['pichu', 'pikachu', 'raichu'],
        ]);

        $this->assertSame('pokedex', $object->dexSlug);
        $this->assertSame('douze', $object->electionSlug);
        $this->assertSame(['pikachu', 'pichu'], $object->winnersSlugs);
        $this->assertSame(['raichu'], $object->losersSlugs);
    }

    #[Test]
    public function withEmptyWinners(): void
    {
        $object = ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'election_slug' => 'douze',
            'winners_slugs' => ['pichu', ''],
            'losers_slugs' => ['pikachu', 'raichu'],
        ]);

        $this->assertSame('pokedex', $object->dexSlug);
        $this->assertSame('douze', $object->electionSlug);
        $this->assertSame(['pichu'], $object->winnersSlugs);
        $this->assertSame(['pikachu', 'raichu'], $object->losersSlugs);
    }

    #[Test]
    public function withEmptyLosers(): void
    {
        $object = ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'election_slug' => 'douze',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu', 'raichu', ''],
        ]);

        $this->assertSame('pokedex', $object->dexSlug);
        $this->assertSame('douze', $object->electionSlug);
        $this->assertSame(['pichu'], $object->winnersSlugs);
        $this->assertSame(['pikachu', 'raichu'], $object->losersSlugs);
    }

    #[Test]
    public function missingDexSlug(): void
    {
        $this->expectException(MissingOptionsException::class);

        ElectionVote::createFromArray([
            'election_slug' => 'douze',
            'winners_slugs' => ['pikachu'],
            'losers_slugs' => ['pichu', 'raichu'],
        ]);
    }

    #[Test]
    public function wrongDexSlug(): void
    {
        $this->expectException(InvalidOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        ElectionVote::createFromArray([
            'dex_slug' => 12,
            'winners_slugs' => ['pikachu'],
            'losers_slugs' => ['pichu', 'raichu'],
        ]);
    }

    #[Test]
    public function missingElectionSlug(): void
    {
        $object = ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'winners_slugs' => ['pikachu'],
            'losers_slugs' => ['pichu', 'raichu'],
        ]);

        $this->assertSame('pokedex', $object->dexSlug);
        $this->assertSame('', $object->electionSlug);
        $this->assertSame(['pikachu'], $object->winnersSlugs);
        $this->assertSame(['pichu', 'raichu'], $object->losersSlugs);
    }

    #[Test]
    public function wrongValueForElectionSlug(): void
    {
        $this->expectException(InvalidOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'election_slug' => false,
            'winners_slugs' => ['pikachu'],
            'losers_slugs' => ['pichu', 'raichu'],
        ]);
    }

    #[Test]
    public function wrongValueForWinnerSlug(): void
    {
        $this->expectException(InvalidOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'winners_slugs' => [54654],
            'losers_slugs' => ['pichu', 'raichu'],
        ]);
    }

    #[Test]
    public function wrongValueForLosersSlugs(): void
    {
        $this->expectException(InvalidOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'winners_slugs' => ['pikachu'],
            'losers_slugs' => 'pichu',
        ]);
    }

    #[Test]
    public function anotherValue(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         */
        ElectionVote::createFromArray([
            'dex_slug' => 'pokedex',
            'election_slug' => 'douze',
            'winners_slugs' => ['pikachu'],
            'losers_slugs' => ['pichu', 'raichu'],
            'other' => 'idk',
        ]);
    }
}
