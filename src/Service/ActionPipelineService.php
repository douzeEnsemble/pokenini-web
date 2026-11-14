<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PipelineStep;
use App\Service\Back\GetActionCatalogService;

/**
 * Orders the update and calculate actions of the catalog so that every action comes after
 * the ones it depends on, keeping the catalog order otherwise.
 */
class ActionPipelineService
{
    private const string DEFAULT_ICON = 'gear';

    /**
     * @var array<string, string>
     */
    private const array ICONS = [
        'update:labels' => 'tags-fill',
        'update:games_collections_and_dex' => 'joystick',
        'update:pokemons' => 'bug-fill',
        'update:regional_dex_numbers' => 'globe-asia-australia',
        'update:games_availabilities' => 'box2',
        'update:games_shinies_availabilities' => 'box2-fill',
        'update:collections_availabilities' => 'collection-fill',
        'calculate:game_bundles_availabilities' => 'box-seam',
        'calculate:game_bundles_shinies_availabilities' => 'box-seam-fill',
        'calculate:dex_availabilities' => 'list-check',
        'calculate:pokemon_availabilities' => 'calculator',
    ];

    public function __construct(
        private readonly GetActionCatalogService $catalog,
    ) {}

    /**
     * @return list<PipelineStep>
     */
    public function steps(): array
    {
        /** @var array<string, array{type: string, name: string, depends_on: list<string>}> $remaining */
        $remaining = [];
        foreach ($this->catalog->get() as $action) {
            $remaining[$action['type'].':'.$action['name']] = $action;
        }

        $sorted = [];
        while ([] !== $remaining) {
            $ready = array_filter(
                $remaining,
                static fn (array $action): bool => [] === array_filter(
                    $action['depends_on'],
                    static fn (string $dependency): bool => isset($remaining[$dependency]),
                ),
            );

            // A dependency cycle cannot be ordered: keep the catalog order for what is left.
            $next = [] === $ready ? $remaining : [array_key_first($ready) => $ready[array_key_first($ready)]];

            foreach ($next as $key => $action) {
                $sorted[$key] = $action;
                unset($remaining[$key]);
            }
        }

        return array_values(array_map(
            fn (array $action): PipelineStep => new PipelineStep(
                $action['type'],
                $action['name'],
                self::page($action['type'], $action['name']),
                self::ICONS[$action['type'].':'.$action['name']] ?? self::DEFAULT_ICON,
                array_map(
                    static function (string $dependency): string {
                        $parts = explode(':', $dependency, 2);
                        $name = $parts[1] ?? '';

                        return 'admin.actions.'.self::page($parts[0], $name).'.'.$name.'.title';
                    },
                    $action['depends_on'],
                ),
            ),
            $sorted,
        ));
    }

    /**
     * The admin page whose translations describe the action.
     */
    private static function page(string $type, string $name): string
    {
        if ('calculate' === $type) {
            return 'calculate_data';
        }

        return str_ends_with($name, '_availabilities') ? 'update_availabilities' : 'update_data';
    }
}
