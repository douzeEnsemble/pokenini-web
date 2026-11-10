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
    /**
     * @param null|string $locale the language of the names, null for both languages
     *
     * @return ColorGameDexListItem[]
     */
    public function getDexList(?string $locale = null): array
    {
        $json = $this->requestContent('GET', '/color_game/dex', self::localeOptions($locale));

        /** @var ColorGameDexListItem[] */
        return $this->serializer->deserialize($json, ColorGameDexListItem::class.'[]', 'json');
    }

    /**
     * @param null|string $locale the language of the names, null for both languages
     *
     * @return ColorGame[]
     */
    public function getGames(?string $locale = null): array
    {
        $json = $this->requestContent('GET', '/color_game/games', self::localeOptions($locale));

        /** @var ColorGame[] */
        return $this->serializer->deserialize($json, ColorGame::class.'[]', 'json');
    }

    public function create(string $dexSlug, ?int $roundCount, ?string $locale = null): ColorGame
    {
        $json = $this->requestContent('POST', '/color_game/games', ['json' => ['dex_slug' => $dexSlug, 'round_count' => $roundCount]] + self::localeOptions($locale));

        /** @var ColorGame */
        return $this->serializer->deserialize($json, ColorGame::class, 'json');
    }

    public function get(string $gameId, ?string $locale = null): ColorGameState
    {
        $json = $this->requestContent('GET', "/color_game/games/{$gameId}", self::localeOptions($locale));

        /** @var ColorGameState */
        return $this->serializer->deserialize($json, ColorGameState::class, 'json');
    }

    public function getRounds(string $gameId, ?string $locale = null): ColorGameRounds
    {
        $json = $this->requestContent('GET', "/color_game/games/{$gameId}/rounds", self::localeOptions($locale));

        /** @var ColorGameRounds */
        return $this->serializer->deserialize($json, ColorGameRounds::class, 'json');
    }

    public function answer(string $gameId, int $position, int $choice, ?string $locale = null): ColorGameAnswer
    {
        $json = $this->requestContent(
            'POST',
            "/color_game/games/{$gameId}/answers",
            ['json' => ['position' => $position, 'choice' => $choice]] + self::localeOptions($locale),
        );

        /** @var ColorGameAnswer */
        return $this->serializer->deserialize($json, ColorGameAnswer::class, 'json');
    }

    /**
     * @return array{query?: array{lang: string}}
     */
    private static function localeOptions(?string $locale): array
    {
        return null === $locale ? [] : ['query' => ['lang' => $locale]];
    }
}
