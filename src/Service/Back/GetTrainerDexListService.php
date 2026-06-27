<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

/**
 * @phpstan-type TrainerDexFlags array{is_shiny: bool, is_private: bool, is_on_home: bool, is_display_form: bool, is_released: bool, is_premium: bool, is_custom: bool}
 * @phpstan-type TrainerDexItem array{slug: string, original_slug: string, name: string, french_name: string, flags: TrainerDexFlags, display_template: string|null, region?: array{name: string, french_name: string, slug: string}}
 *
 * @psalm-type TrainerDexFlags array{is_shiny: bool, is_private: bool, is_on_home: bool, is_display_form: bool, is_released: bool, is_premium: bool, is_custom: bool}
 * @psalm-type TrainerDexItem array{slug: string, original_slug: string, name: string, french_name: string, flags: TrainerDexFlags, display_template: string|null, region?: array{name: string, french_name: string, slug: string}}
 */
class GetTrainerDexListService extends AbstractBackService
{
    /**
     * @return list<TrainerDexItem>
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/trainer/dex',
        );

        /** @var list<TrainerDexItem> */
        return JsonDecoder::decode($json);
    }
}
