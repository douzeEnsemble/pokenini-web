<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\ShinyGame\ShinyGameAnswer;
use App\ResponseObject\ShinyGame\ShinyGameDexListItem;
use App\ResponseObject\ShinyGame\ShinyGameRounds;
use App\ResponseObject\ShinyGame\ShinyGameState;

class ShinyGameService extends AbstractBackService
{
    /** @return ShinyGameDexListItem[] */
    public function getDexList(): array
    {
        $json = $this->requestContent('GET', '/shiny_game/dex');

        /** @var ShinyGameDexListItem[] */
        return $this->serializer->deserialize($json, ShinyGameDexListItem::class.'[]', 'json');
    }

    /** @return ShinyGame[] */
    public function getGames(): array
    {
        $json = $this->requestContent('GET', '/shiny_game/games');

        /** @var ShinyGame[] */
        return $this->serializer->deserialize($json, ShinyGame::class.'[]', 'json');
    }

    public function create(string $dexSlug, ?int $roundCount): ShinyGame
    {
        $json = $this->requestContent('POST', '/shiny_game/games', ['json' => ['dex_slug' => $dexSlug, 'round_count' => $roundCount]]);

        /** @var ShinyGame */
        return $this->serializer->deserialize($json, ShinyGame::class, 'json');
    }

    public function get(string $gameId): ShinyGameState
    {
        $json = $this->requestContent('GET', "/shiny_game/games/{$gameId}");

        /** @var ShinyGameState */
        return $this->serializer->deserialize($json, ShinyGameState::class, 'json');
    }

    public function getRounds(string $gameId): ShinyGameRounds
    {
        $json = $this->requestContent('GET', "/shiny_game/games/{$gameId}/rounds");

        /** @var ShinyGameRounds */
        return $this->serializer->deserialize($json, ShinyGameRounds::class, 'json');
    }

    public function answer(string $gameId, int $position, bool $isShiny): ShinyGameAnswer
    {
        $json = $this->requestContent(
            'POST',
            "/shiny_game/games/{$gameId}/answers",
            ['json' => ['position' => $position, 'is_shiny' => $isShiny]],
        );

        /** @var ShinyGameAnswer */
        return $this->serializer->deserialize($json, ShinyGameAnswer::class, 'json');
    }
}
