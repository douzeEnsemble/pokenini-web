<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameAnswer;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexListItem;
use App\ResponseObject\WhichShinyGame\WhichShinyGameRounds;
use App\ResponseObject\WhichShinyGame\WhichShinyGameState;

class WhichShinyGameService extends AbstractBackService
{
    /**
     * @param null|string $locale the language of the names, null for both languages
     *
     * @return WhichShinyGameDexListItem[]
     */
    public function getDexList(?string $locale = null): array
    {
        $json = $this->requestContent('GET', '/which_shiny_game/dex', self::localeOptions($locale));

        /** @var WhichShinyGameDexListItem[] */
        return $this->serializer->deserialize($json, WhichShinyGameDexListItem::class.'[]', 'json');
    }

    /**
     * @param null|string $locale the language of the names, null for both languages
     *
     * @return WhichShinyGame[]
     */
    public function getGames(?string $locale = null): array
    {
        $json = $this->requestContent('GET', '/which_shiny_game/games', self::localeOptions($locale));

        /** @var WhichShinyGame[] */
        return $this->serializer->deserialize($json, WhichShinyGame::class.'[]', 'json');
    }

    public function create(string $dexSlug, ?int $roundCount, ?string $locale = null): WhichShinyGame
    {
        $json = $this->requestContent('POST', '/which_shiny_game/games', ['json' => ['dex_slug' => $dexSlug, 'round_count' => $roundCount]] + self::localeOptions($locale));

        /** @var WhichShinyGame */
        return $this->serializer->deserialize($json, WhichShinyGame::class, 'json');
    }

    public function get(string $gameId, ?string $locale = null): WhichShinyGameState
    {
        $json = $this->requestContent('GET', "/which_shiny_game/games/{$gameId}", self::localeOptions($locale));

        /** @var WhichShinyGameState */
        return $this->serializer->deserialize($json, WhichShinyGameState::class, 'json');
    }

    public function getRounds(string $gameId, ?string $locale = null): WhichShinyGameRounds
    {
        $json = $this->requestContent('GET', "/which_shiny_game/games/{$gameId}/rounds", self::localeOptions($locale));

        /** @var WhichShinyGameRounds */
        return $this->serializer->deserialize($json, WhichShinyGameRounds::class, 'json');
    }

    public function answer(string $gameId, int $position, int $slot, ?string $locale = null): WhichShinyGameAnswer
    {
        $json = $this->requestContent(
            'POST',
            "/which_shiny_game/games/{$gameId}/answers",
            ['json' => ['position' => $position, 'slot' => $slot]] + self::localeOptions($locale),
        );

        /** @var WhichShinyGameAnswer */
        return $this->serializer->deserialize($json, WhichShinyGameAnswer::class, 'json');
    }

    /**
     * @return array{query?: array{lang: string}}
     */
    private static function localeOptions(?string $locale): array
    {
        return null === $locale ? [] : ['query' => ['lang' => $locale]];
    }
}
