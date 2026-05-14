<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionVote
{
    /**
     * @param array<int, string> $winnersSlugs
     * @param array<int, string> $losersSlugs
     */
    private function __construct(
        public readonly string $dexSlug,
        public readonly string $electionSlug,
        public readonly array $winnersSlugs,
        public readonly array $losersSlugs,
    ) {}

    /**
     * @param array{
     *  dex_slug?: string,
     *  election_slug?: string,
     *  winners_slugs?: array<int, string>,
     *  losers_slugs?: array<int, string>,
     * } $values
     */
    public static function createFromArray(array $values = []): self
    {
        $resolver = new OptionsResolver();
        self::configureOptions($resolver);

        /**
         * @var array{
         *  dex_slug: string,
         *  election_slug: string,
         *  winners_slugs: array<int, string>,
         *  losers_slugs: array<int, string>,
         * }
         */
        $options = $resolver->resolve($values);

        /** @var array<int, string> $winnersSlugs */
        $winnersSlugs = array_filter($options['winners_slugs']);

        $nonWinnersSlugs = array_diff(array_filter($options['losers_slugs']), $winnersSlugs);

        /** @var array<int, string> $losersSlugs */
        $losersSlugs = array_values($nonWinnersSlugs);

        return new self(
            $options['dex_slug'],
            $options['election_slug'],
            $winnersSlugs,
            $losersSlugs,
        );
    }

    private static function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('dex_slug');
        $resolver->setAllowedTypes('dex_slug', 'string');

        $resolver->setDefault('election_slug', '');
        $resolver->setAllowedTypes('election_slug', 'string');

        $resolver->setRequired('winners_slugs');
        $resolver->setAllowedTypes('winners_slugs', 'string[]');

        $resolver->setRequired('losers_slugs');
        $resolver->setAllowedTypes('losers_slugs', 'string[]');
    }
}
