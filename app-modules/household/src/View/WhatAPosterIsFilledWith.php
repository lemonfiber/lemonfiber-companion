<?php

declare(strict_types=1);

namespace Modules\Household\View;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

use Modules\Household\Internal\ViewModels\WhatOnePosterSays;

/**
 * The words a poster's lines are filled with: those written as they are, and
 * those that are keys, a kind or a standing, said in the reader's language
 * first.
 *
 * Shared by the poster and the hero, which say the same two lines about the
 * same title at two sizes.
 */
final readonly class WhatAPosterIsFilledWith
{
    /** @param array<string, string> $filling */
    private function __construct(
        private Translator $catalogue,
        private array $filling,
    ) {}

    /** What one poster's lines are filled with. */
    public static function by(Translator $catalogue, WhatOnePosterSays $poster): self
    {
        $filling = $poster->filling;

        foreach ($poster->keyed as $name => $key) {
            $said = $catalogue->get($key);
            $filling[$name] = is_string($said) ? $said : $key;
        }

        return new self($catalogue, $filling);
    }

    /** One of its lines, filled, or what stands in where the catalogue has no line for it. */
    public function line(string $key, string $otherwise): string
    {
        $said = $this->catalogue->get($key, $this->filling);

        return is_string($said) ? $said : $otherwise;
    }
}
