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
    /**
     * @param null|string $locale the language of the names, null for both languages
     *
     * @return ShinyGameDexListItem[]
     */
    public function getDexList(?string $locale = null): array
    {
        $json = $this->requestContent('GET', '/shiny_game/dex', self::localeOptions($locale));

        /** @var ShinyGameDexListItem[] */
        return $this->serializer->deserialize($json, ShinyGameDexListItem::class.'[]', 'json');
    }

    /**
     * @param null|string $locale the language of the names, null for both languages
     *
     * @return ShinyGame[]
     */
    public function getGames(?string $locale = null): array
    {
        $json = $this->requestContent('GET', '/shiny_game/games', self::localeOptions($locale));

        /** @var ShinyGame[] */
        return $this->serializer->deserialize($json, ShinyGame::class.'[]', 'json');
    }

    public function create(string $dexSlug, ?int $roundCount, ?string $locale = null): ShinyGame
    {
        $json = $this->requestContent('POST', '/shiny_game/games', ['json' => ['dex_slug' => $dexSlug, 'round_count' => $roundCount]] + self::localeOptions($locale));

        /** @var ShinyGame */
        return $this->serializer->deserialize($json, ShinyGame::class, 'json');
    }

    public function get(string $gameId, ?string $locale = null): ShinyGameState
    {
        $json = $this->requestContent('GET', "/shiny_game/games/{$gameId}", self::localeOptions($locale));

        /** @var ShinyGameState */
        return $this->serializer->deserialize($json, ShinyGameState::class, 'json');
    }

    public function getRounds(string $gameId, ?string $locale = null): ShinyGameRounds
    {
        $json = $this->requestContent('GET', "/shiny_game/games/{$gameId}/rounds", self::localeOptions($locale));

        /** @var ShinyGameRounds */
        return $this->serializer->deserialize($json, ShinyGameRounds::class, 'json');
    }

    public function answer(string $gameId, int $position, bool $isShiny, ?string $locale = null): ShinyGameAnswer
    {
        $json = $this->requestContent(
            'POST',
            "/shiny_game/games/{$gameId}/answers",
            ['json' => ['position' => $position, 'is_shiny' => $isShiny]] + self::localeOptions($locale),
        );

        /** @var ShinyGameAnswer */
        return $this->serializer->deserialize($json, ShinyGameAnswer::class, 'json');
    }

    /**
     * @return array{query?: array{lang: string}}
     */
    private static function localeOptions(?string $locale): array
    {
        return null === $locale ? [] : ['query' => ['lang' => $locale]];
    }
}
