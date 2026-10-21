<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameAnswer;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexListItem;
use App\ResponseObject\WhichShinyGame\WhichShinyGameState;

class WhichShinyGameService extends AbstractBackService
{
    /** @return WhichShinyGameDexListItem[] */
    public function getDexList(): array
    {
        $json = $this->requestContent('GET', '/which_shiny_game/dex');

        /** @var WhichShinyGameDexListItem[] */
        return $this->serializer->deserialize($json, WhichShinyGameDexListItem::class.'[]', 'json');
    }

    /** @return WhichShinyGame[] */
    public function getGames(): array
    {
        $json = $this->requestContent('GET', '/which_shiny_game/games');

        /** @var WhichShinyGame[] */
        return $this->serializer->deserialize($json, WhichShinyGame::class.'[]', 'json');
    }

    public function create(string $dexSlug): WhichShinyGame
    {
        $json = $this->requestContent('POST', '/which_shiny_game/games', ['json' => ['dex_slug' => $dexSlug]]);

        /** @var WhichShinyGame */
        return $this->serializer->deserialize($json, WhichShinyGame::class, 'json');
    }

    public function get(string $gameId): WhichShinyGameState
    {
        $json = $this->requestContent('GET', "/which_shiny_game/games/{$gameId}");

        /** @var WhichShinyGameState */
        return $this->serializer->deserialize($json, WhichShinyGameState::class, 'json');
    }

    public function answer(string $gameId, int $position, int $slot): WhichShinyGameAnswer
    {
        $json = $this->requestContent(
            'POST',
            "/which_shiny_game/games/{$gameId}/answers",
            ['json' => ['position' => $position, 'slot' => $slot]],
        );

        /** @var WhichShinyGameAnswer */
        return $this->serializer->deserialize($json, WhichShinyGameAnswer::class, 'json');
    }
}
