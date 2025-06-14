<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionVote
{
    public string $dexSlug;
    public string $electionSlug;

    /**
     * @var string[]
     */
    public array $winnersSlugs;

    /**
     * @var string[]
     */
    public array $losersSlugs;

    /**
     * @param string[]|string[][] $values
     */
    public function __construct(array $values = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $options = $resolver->resolve($values);

        $this->dexSlug = $options['dex_slug'];
        $this->electionSlug = $options['election_slug'];
        $this->winnersSlugs = array_filter($options['winners_slugs']);
        $this->losersSlugs = array_diff(array_filter($options['losers_slugs']), $this->winnersSlugs);

        $this->losersSlugs = array_values($this->losersSlugs);
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
