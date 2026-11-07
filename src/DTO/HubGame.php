<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGame;

/**
 * One game shown on the hub: the trainer's game in progress, else their last game, else none.
 */
final class HubGame
{
    public const string COLOR = 'color_game';
    public const string SHINY = 'shiny_game';
    public const string WHICH_SHINY = 'which_shiny_game';

    /**
     * The route prefix of each game: "<prefix>_dex", "<prefix>_play", "<prefix>_create".
     */
    private const array ROUTE_PREFIXES = [
        self::COLOR => 'app_colorgame',
        self::SHINY => 'app_shinygame',
        self::WHICH_SHINY => 'app_whichshinygame',
    ];

    private const array ICONS = [
        self::COLOR => 'img/game/color.svg',
        self::SHINY => 'img/game/shiny.svg',
        self::WHICH_SHINY => 'img/game/which-shiny.svg',
    ];

    public function __construct(
        public readonly string $type,
        public readonly ColorGame|ShinyGame|WhichShinyGame|null $game,
    ) {}

    /**
     * Picks the game in progress played last, else the game played last, else none.
     *
     * @param ColorGame[]|ShinyGame[]|WhichShinyGame[] $games
     */
    public static function fromGames(string $type, array $games): self
    {
        $inProgress = array_filter(
            $games,
            static fn (ColorGame|ShinyGame|WhichShinyGame $game): bool => 'in_progress' === $game->getStatus(),
        );

        return new self($type, self::lastPlayed($inProgress) ?? self::lastPlayed($games));
    }

    public function getTitle(): string
    {
        return $this->type.'.title';
    }

    public function getIcon(): string
    {
        return self::ICONS[$this->type];
    }

    public function getCsrfTokenId(): string
    {
        return $this->type;
    }

    public function getDexRoute(): string
    {
        return self::ROUTE_PREFIXES[$this->type].'_dex';
    }

    public function getPlayRoute(): string
    {
        return self::ROUTE_PREFIXES[$this->type].'_play';
    }

    public function getCreateRoute(): string
    {
        return self::ROUTE_PREFIXES[$this->type].'_create';
    }

    public function isInProgress(): bool
    {
        return null !== $this->game && 'in_progress' === $this->game->getStatus();
    }

    /**
     * @param ColorGame[]|ShinyGame[]|WhichShinyGame[] $games
     */
    private static function lastPlayed(array $games): ColorGame|ShinyGame|WhichShinyGame|null
    {
        $last = null;

        foreach ($games as $game) {
            if (null === $last || self::playedAt($game) > self::playedAt($last)) {
                $last = $game;
            }
        }

        return $last;
    }

    private static function playedAt(ColorGame|ShinyGame|WhichShinyGame $game): \DateTimeImmutable
    {
        return new \DateTimeImmutable($game->getLastPlayedAt() ?? $game->getStartedAt());
    }
}
