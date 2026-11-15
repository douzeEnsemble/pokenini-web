<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\PipelineStep;
use App\Service\ActionPipelineService;
use App\Service\Back\GetActionCatalogService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ActionPipelineService::class)]
#[CoversClass(PipelineStep::class)]
final class ActionPipelineServiceTest extends TestCase
{
    #[Test]
    public function stepsComeAfterTheirDependencies(): void
    {
        $steps = $this->service([
            ['type' => 'calculate', 'name' => 'pokemon_availabilities', 'depends_on' => ['calculate:dex_availabilities']],
            ['type' => 'calculate', 'name' => 'dex_availabilities', 'depends_on' => ['update:games_availabilities', 'update:collections_availabilities']],
            ['type' => 'update', 'name' => 'games_availabilities', 'depends_on' => []],
            ['type' => 'update', 'name' => 'labels', 'depends_on' => []],
            ['type' => 'update', 'name' => 'collections_availabilities', 'depends_on' => []],
        ])->steps();

        $this->assertSame(
            [
                'update:games_availabilities',
                'update:labels',
                'update:collections_availabilities',
                'calculate:dex_availabilities',
                'calculate:pokemon_availabilities',
            ],
            array_map(static fn (PipelineStep $step): string => $step->type.':'.$step->name, $steps),
        );
    }

    #[Test]
    public function stepsOfKeepsTheStepsOfOnePageInOrder(): void
    {
        $steps = $this->service([
            ['type' => 'calculate', 'name' => 'dex_availabilities', 'depends_on' => ['update:games_availabilities']],
            ['type' => 'update', 'name' => 'games_availabilities', 'depends_on' => []],
            ['type' => 'update', 'name' => 'labels', 'depends_on' => []],
            ['type' => 'calculate', 'name' => 'pokemon_availabilities', 'depends_on' => ['calculate:dex_availabilities']],
        ])->stepsOf('calculate_data');

        $this->assertSame(
            ['dex_availabilities', 'pokemon_availabilities'],
            array_map(static fn (PipelineStep $step): string => $step->name, $steps),
        );
    }

    #[Test]
    public function stepsKeepTheCatalogOrderWithoutDependencies(): void
    {
        $steps = $this->service([
            ['type' => 'update', 'name' => 'pokemons', 'depends_on' => []],
            ['type' => 'update', 'name' => 'labels', 'depends_on' => []],
        ])->steps();

        $this->assertSame(['pokemons', 'labels'], array_map(static fn (PipelineStep $step): string => $step->name, $steps));
    }

    #[Test]
    public function stepsDescribeTheirPageIconAndDependencies(): void
    {
        $steps = $this->service([
            ['type' => 'update', 'name' => 'games_availabilities', 'depends_on' => []],
            ['type' => 'calculate', 'name' => 'dex_availabilities', 'depends_on' => ['update:games_availabilities', 'calculate:game_bundles_availabilities']],
            ['type' => 'calculate', 'name' => 'game_bundles_availabilities', 'depends_on' => []],
            ['type' => 'update', 'name' => 'labels', 'depends_on' => []],
        ])->steps();

        $byName = array_column($steps, null, 'name');

        $this->assertSame('update_availabilities', $byName['games_availabilities']->page);
        $this->assertSame('box2', $byName['games_availabilities']->icon);
        $this->assertSame('update_data', $byName['labels']->page);
        $this->assertSame('calculate_data', $byName['dex_availabilities']->page);
        $this->assertSame('list-check', $byName['dex_availabilities']->icon);
        $this->assertSame(
            [
                'admin.actions.update_availabilities.games_availabilities.title',
                'admin.actions.calculate_data.game_bundles_availabilities.title',
            ],
            $byName['dex_availabilities']->dependsOnTitleKeys,
        );
    }

    #[Test]
    public function onlyTheFirstColonSeparatesTheTypeFromTheName(): void
    {
        $steps = $this->service([
            ['type' => 'update', 'name' => 'labels', 'depends_on' => ['update:labels:extra']],
        ])->steps();

        $this->assertSame(
            ['admin.actions.update_data.labels:extra.title'],
            $steps[0]->dependsOnTitleKeys,
        );
    }

    #[Test]
    public function unknownActionsGetTheDefaultIcon(): void
    {
        $steps = $this->service([
            ['type' => 'update', 'name' => 'brand_new', 'depends_on' => ['update:not_in_catalog']],
        ])->steps();

        $this->assertCount(1, $steps);
        $this->assertSame('gear', $steps[0]->icon);
    }

    #[Test]
    public function aDependencyCycleKeepsTheCatalogOrder(): void
    {
        $steps = $this->service([
            ['type' => 'update', 'name' => 'a', 'depends_on' => ['update:b']],
            ['type' => 'update', 'name' => 'b', 'depends_on' => ['update:a']],
        ])->steps();

        $this->assertSame(['a', 'b'], array_map(static fn (PipelineStep $step): string => $step->name, $steps));
    }

    /**
     * @param list<array{type: string, name: string, depends_on: list<string>}> $catalog
     */
    private function service(array $catalog): ActionPipelineService
    {
        $service = $this->createStub(GetActionCatalogService::class);
        $service->method('get')->willReturn($catalog);

        return new ActionPipelineService($service);
    }
}
