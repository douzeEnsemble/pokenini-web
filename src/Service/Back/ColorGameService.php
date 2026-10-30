<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameAnswer;
use App\ResponseObject\ColorGame\ColorGameDexListItem;
use App\ResponseObject\ColorGame\ColorGameRounds;
use App\ResponseObject\ColorGame\ColorGameState;

class ColorGameService extends AbstractBackService
{
    /** @return ColorGameDexListItem[] */
    public function getDexList(): array
    {
        $json = $this->requestContent('GET', '/color_game/dex');

        /** @var ColorGameDexListItem[] */
        return $this->serializer->deserialize($json, ColorGameDexListItem::class.'[]', 'json');
    }

    /** @return ColorGame[] */
    public function getGames(): array
    {
        $json = $this->requestContent('GET', '/color_game/games');

        /** @var ColorGame[] */
        return $this->serializer->deserialize($json, ColorGame::class.'[]', 'json');
    }

    public function create(string $dexSlug, ?int $roundCount): ColorGame
    {
        $json = $this->requestContent('POST', '/color_game/games', ['json' => ['dex_slug' => $dexSlug, 'round_count' => $roundCount]]);

        /** @var ColorGame */
        return $this->serializer->deserialize($json, ColorGame::class, 'json');
    }

    public function get(string $gameId): ColorGameState
    {
        $json = $this->requestContent('GET', "/color_game/games/{$gameId}");

        /** @var ColorGameState */
        return $this->serializer->deserialize($json, ColorGameState::class, 'json');
    }

    public function getRounds(string $gameId): ColorGameRounds
    {
        $json = $this->requestContent('GET', "/color_game/games/{$gameId}/rounds");

        /** @var ColorGameRounds */
        return $this->serializer->deserialize($json, ColorGameRounds::class, 'json');
    }

    public function answer(string $gameId, int $position, int $choice): ColorGameAnswer
    {
        $json = $this->requestContent(
            'POST',
            "/color_game/games/{$gameId}/answers",
            ['json' => ['position' => $position, 'choice' => $choice]],
        );

        /** @var ColorGameAnswer */
        return $this->serializer->deserialize($json, ColorGameAnswer::class, 'json');
    }
}
