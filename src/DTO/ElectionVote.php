<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionVote
{
    public string $dexSlug;
    public string $electionSlug;

    /**
     * @var array<int, string>
     */
    public array $winnersSlugs;

    /**
     * @var array<int, string>
     */
    public array $losersSlugs;

    /**
     * @param array{
     *  dex_slug?: string,
     *  election_slug?: string,
     *  winners_slugs?: array<int, string>,
     *  losers_slugs?: array<int, string>,
     * } $values
     */
    public function __construct(array $values = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        /**
         * @var array{
         *  dex_slug: string,
         *  election_slug: string,
         *  winners_slugs: array<int, string>,
         *  losers_slugs: array<int, string>,
         * }
         */
        $options = $resolver->resolve($values);

        $this->dexSlug = $options['dex_slug'];
        $this->electionSlug = $options['election_slug'];

        /** @var array<int, string> $winnersSlugs */
        $winnersSlugs = array_filter($options['winners_slugs']);

        $nonWinnersSlugs = array_diff(array_filter($options['losers_slugs']), $winnersSlugs);

        /** @var array<int, string> $losersSlugs */
        $losersSlugs = array_values($nonWinnersSlugs);

        $this->winnersSlugs = $winnersSlugs;
        $this->losersSlugs = $losersSlugs;
    }

    private function configureOptions(OptionsResolver $resolver): void
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
