<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\ExtendingIt;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\WhenARecipeRuns;

/**
 * Which plugin a row of the listing last read is, which of its demand recipes a place names, and whether an act may be asked about them.
 *
 * Apart from the screen, which asks it, so that what a row offers is decided
 * in one place and the screen only puts the question.
 */
final readonly class WhatARowOffers
{
    public function __construct(private ThePlugins $listing) {}

    /** The plugin in that row, or none for a row the listing does not have. */
    public function plugin(int $which): ?APlugin
    {
        $at = 0;

        foreach ($this->listing->installed() as $plugin) {
            if ($at === $which) {
                return $plugin;
            }

            $at++;
        }

        return null;
    }

    /** The recipe in that place among the plugin's, where it runs on demand and asks the operator for no value. */
    public function demandRecipe(APlugin $plugin, ?int $place): ?ARecipe
    {
        $at = 0;

        foreach ($plugin->recipes() as $recipe) {
            if ($at === $place) {
                return $recipe->runs() === WhenARecipeRuns::Demand && $recipe->asks()->count() === 0 ? $recipe : null;
            }

            $at++;
        }

        return null;
    }

    /** Whether the act may be asked about this plugin: an update needs a source, a proof something kept against it, and a run a recipe it may run. */
    public function offers(ExtendingIt $act, APlugin $plugin, ?ARecipe $recipe): bool
    {
        return match ($act) {
            ExtendingIt::Update => $plugin->canBeUpdated(),
            ExtendingIt::Prove => $this->listing->answersOutOfContractOf($plugin)->holdsAny(),
            ExtendingIt::Run => $recipe instanceof ARecipe,
            ExtendingIt::Install, ExtendingIt::Remove => true,
        };
    }
}
