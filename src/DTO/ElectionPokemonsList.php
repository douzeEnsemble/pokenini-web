<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionPokemonsList
{
    public string $type;

    /**
     * @var array{null|int|string}
     */
    public array $items;

    /**
     * @param array{type: string, items: list<array<array-key, null|int|string>>} $values
     */
    public function __construct(array $values)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $options = $resolver->resolve($values);

        $this->type = $options['type'];
        $this->items = $options['items'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('type');
        $resolver->setAllowedTypes('type', 'string');

        $resolver->setRequired('items');
        $resolver->setAllowedTypes('items', 'array[]');
    }
}
