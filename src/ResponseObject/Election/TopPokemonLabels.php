<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use App\ResponseObject\Common\NamesMap;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The names of a Pokémon of the top, whichever shape the back used:
 * - legacy: `name` and `french_name` (and the simplified ones, and the forms labels, in both languages)
 * - by locale: `names` and `simplified_names`, the forms label being in a single language.
 *
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class TopPokemonLabels
{
    /**
     * @param null|array<string, string> $names           the names indexed by locale
     * @param null|array<string, string> $simplifiedNames the simplified names indexed by locale
     */
    public function __construct(
        #[SerializedName('name')]
        private readonly ?string $name = null,
        #[SerializedName('french_name')]
        private readonly ?string $frenchName = null,
        #[SerializedName('simplified_name')]
        private readonly ?string $simplifiedName = null,
        #[SerializedName('simplified_french_name')]
        private readonly ?string $simplifiedFrenchName = null,
        #[SerializedName('forms_label')]
        private readonly ?string $formsLabel = null,
        #[SerializedName('forms_french_label')]
        private readonly ?string $formsFrenchLabel = null,
        #[SerializedName('names')]
        private readonly ?array $names = null,
        #[SerializedName('simplified_names')]
        private readonly ?array $simplifiedNames = null,
    ) {}

    public function getName(): string
    {
        return (null !== $this->names ? ($this->names['en'] ?? null) : $this->name) ?? '';
    }

    public function getFrenchName(): string
    {
        return (null !== $this->names ? ($this->names['fr'] ?? null) : $this->frenchName) ?? '';
    }

    /**
     * @return array<string, string> the names indexed by locale
     */
    public function getNames(): array
    {
        if (null !== $this->names) {
            return $this->names;
        }

        return ['en' => $this->getName(), 'fr' => $this->getFrenchName()];
    }

    public function getSimplifiedName(): string
    {
        return (null !== $this->simplifiedNames ? ($this->simplifiedNames['en'] ?? null) : $this->simplifiedName) ?? '';
    }

    public function getSimplifiedFrenchName(): string
    {
        return (null !== $this->simplifiedNames ? ($this->simplifiedNames['fr'] ?? null) : $this->simplifiedFrenchName) ?? '';
    }

    /**
     * @return array<string, string> the simplified names indexed by locale
     */
    public function getSimplifiedNames(): array
    {
        if (null !== $this->simplifiedNames) {
            return $this->simplifiedNames;
        }

        return ['en' => $this->getSimplifiedName(), 'fr' => $this->getSimplifiedFrenchName()];
    }

    public function getFormsLabel(): ?string
    {
        return $this->formsLabel;
    }

    public function getFormsFrenchLabel(): ?string
    {
        return $this->formsFrenchLabel;
    }

    /**
     * @return array<string, string> the forms label indexed by locale
     */
    public function getFormsLabels(): array
    {
        if (null === $this->names) {
            return ['en' => $this->formsLabel ?? '', 'fr' => $this->formsFrenchLabel ?? ''];
        }

        return null === $this->formsLabel ? [] : [NamesMap::RESOLVED => $this->formsLabel];
    }
}
