<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_search;
use function in_array;
use function is_int;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\HowItsSourceStands;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\ThePlugins;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\AChangeAndWhyAsShown;
use Modules\Operator\Internal\ViewModels\AContestAsShown;
use Modules\Operator\Internal\ViewModels\APluginAsShown;
use Modules\Operator\Internal\ViewModels\APluginChangeAsShown;
use Modules\Operator\Internal\ViewModels\APluginInstallAsShown;
use Modules\Operator\Internal\ViewModels\AProofAsShown;
use Modules\Operator\Internal\ViewModels\ARecipeAsShown;
use Modules\Operator\Internal\ViewModels\ARecipeStepAsShown;
use Modules\Operator\Internal\ViewModels\AValueCarriedAsShown;
use Modules\Operator\Internal\ViewModels\HowPuttingARunBackWent;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatExtendsItTurnedOutToBe;

/**
 * The plugins on a stack, and installing one, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state, each saying only its
 * own, so a template never offers Install beneath an account that is not a
 * reading, and never draws an install as installed short of all three things
 * that make it one.
 */
final readonly class HowExtendingItReads
{
    /** This device no longer holds a session for the stack. */
    public function signedOut(): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    /** Asking met this instead. */
    public function met(Obstacle $why, bool $installing): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why), installing: $installing);
    }

    /** A source is being typed, and nothing has been asked. */
    public function typing(): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), typing: true);
    }

    /** The stack is at work on the rehearsal, or on the install. */
    public function running(bool $installing): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), isWorking: true, installing: $installing);
    }

    /** The stack has no outcome for the work any more, which is not the same as it not having happened. */
    public function ended(bool $installing): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), installing: $installing, hasEnded: true);
    }

    /** The stack refused, and this is what it said and named. */
    public function refused(ARefusalInItsWords $why, bool $installing): WhatExtendsItTurnedOutToBe
    {
        return new WhatExtendsItTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            typing: false,
            isWorking: false,
            installing: $installing,
            hasEnded: false,
            refused: new HowARefusalReads()->inItsWords($why),
            installed: [],
            install: null,
        );
    }

    /**
     * What the stack said of its plugins: the listing, and the install's account where it is about one.
     *
     * @param list<string> $approved every value the operator has approved so far, as the reading spells each
     */
    public function answered(ThePlugins $plugins, array $approved, bool $installing): WhatExtendsItTurnedOutToBe
    {
        $installed = [];

        foreach ($plugins->installed() as $plugin) {
            $installed[] = $this->plugin($plugin, $plugins->sourceOf($plugin), $approved);
        }

        $install = $plugins->either(
            listed: static fn(): AsText => AsText::nothing(),
            install: fn(APluginInstall $install): APluginInstallAsShown => $this->install($install, $approved, agreeable: $plugins->agreement() !== ''),
        );

        return new WhatExtendsItTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            typing: false,
            isWorking: false,
            installing: $installing,
            hasEnded: false,
            refused: null,
            installed: $installed,
            install: $install instanceof APluginInstallAsShown ? $install : null,
        );
    }

    /**
     * An install's account.
     *
     * @param list<string> $approved
     */
    private function install(APluginInstall $install, array $approved, bool $agreeable): APluginInstallAsShown
    {
        $changes = [];

        foreach ($install->changes() as $change) {
            $changes[] = new APluginChangeAsShown(path: $change->path(), putsSaid: $change->puts()->saidOnTheScreen());
        }

        $proofs = [];

        foreach ($install->proofs() as $proof) {
            $proofs[] = new AProofAsShown(
                establishes: $proof->establishes(),
                asks: $proof->asks(),
                why: $proof->why(),
                cameToSaid: $proof->cameTo()->says()->saidOnTheScreen(),
                said: $this->lines($proof->cameTo()->said()),
            );
        }

        $overrides = [];

        foreach ($install->overrides() as $override) {
            $overrides[] = new AChangeAndWhyAsShown(target: $override->setting(), because: $override->why());
        }

        $contests = [];

        foreach ($install->contests() as $contest) {
            $contests[] = new AContestAsShown(capability: $contest->capability(), by: $contest->by(), claimants: $this->lines($contest->claimants()));
        }

        $checks = $install->checks();
        $putBack = $install->wasItPutBack(
            putBack: static fn(ARunPutBack $report): HowPuttingARunBackWent => new HowARunBackReads()->done($report),
            notPutBack: static fn(): AsText => AsText::nothing(),
        );

        return new APluginInstallAsShown(
            plugin: $this->plugin($install->would(), HowItsSourceStands::notSaid(), $approved),
            isAReading: $install->isAReading(),
            agreeable: $agreeable,
            headline: $this->headline($install),
            changes: $changes,
            proofs: $proofs,
            overrides: $overrides,
            contests: $contests,
            checked: $checks->wereAsked(),
            broke: $this->lines($checks->broke()),
            unsettled: $this->lines($checks->unsettled()),
            putBack: $putBack instanceof HowPuttingARunBackWent ? $putBack : null,
        );
    }

    /** How an install ended, as a catalogue key, or empty for a reading. */
    private function headline(APluginInstall $install): string
    {
        return match (true) {
            $install->isAReading() => '',
            $install->held() => 'plugins.installed_it',
            default => $install->wasItPutBack(
                putBack: static fn(): AsText => AsText::of('plugins.put_back'),
                notPutBack: static fn(): AsText => AsText::of('plugins.not_installed'),
            )->said,
        };
    }

    /**
     * One plugin, with every recipe in full.
     *
     * @param list<string> $approved
     */
    private function plugin(APlugin $plugin, HowItsSourceStands $standing, array $approved): APluginAsShown
    {
        $vouched = $plugin->vouched();
        $recipes = [];

        foreach ($plugin->recipes() as $recipe) {
            $recipes[] = $this->recipe($recipe, $this->lines($plugin->approvals()), $approved);
        }

        return new APluginAsShown(
            name: $plugin->shown(),
            id: $plugin->id(),
            version: $plugin->version(),
            reviewed: $vouched->wasReviewed(),
            source: $vouched->source(),
            revision: $vouched->revision(),
            signed: $vouched->signed(),
            upstream: $vouched->upstream(),
            licence: $vouched->licence(),
            standingSaid: $standing->standing()->saidOnTheScreen(),
            standingWhy: $standing->why(),
            recipes: $recipes,
        );
    }

    /**
     * One recipe, each pair with its switch where it asks for an approval.
     *
     * @param list<string> $approvals every approval the plugin's recipes ask for
     * @param list<string> $approved  the ones the operator has given
     */
    private function recipe(ARecipe $recipe, array $approvals, array $approved): ARecipeAsShown
    {
        $steps = [];

        foreach ($recipe->steps() as $step) {
            $steps[] = new ARecipeStepAsShown(method: $step->method(), to: $step->to(), path: $step->path(), adapter: $step->adapter());
        }

        $pairs = [];

        foreach ($recipe->pairs() as $pair) {
            $place = $pair->asksForApproval() ? array_search($pair->approval(), $approvals, strict: true) : false;

            $pairs[] = new AValueCarriedAsShown(
                value: $pair->value(),
                origin: $pair->origin(),
                to: $pair->to(),
                release: $pair->release(),
                from: $pair->from(),
                approval: is_int($place) ? $place : null,
                approved: $pair->asksForApproval() && in_array($pair->approval(), $approved, strict: true),
            );
        }

        return new ARecipeAsShown(title: $recipe->title(), why: $recipe->why(), steps: $steps, pairs: $pairs);
    }

    /**
     * Lines the stack said, collected by hand into the list a template reads.
     *
     * @return list<string>
     */
    private function lines(PluginLines $lines): array
    {
        $listed = [];

        foreach ($lines as $line) {
            $listed[] = $line;
        }

        return $listed;
    }

    /** A state with neither a listing nor an account in it. */
    private function without(
        HowTheReadingWent $went,
        bool $typing = false,
        bool $isWorking = false,
        bool $installing = false,
        bool $hasEnded = false,
    ): WhatExtendsItTurnedOutToBe {
        return new WhatExtendsItTurnedOutToBe(
            went: $went,
            typing: $typing,
            isWorking: $isWorking,
            installing: $installing,
            hasEnded: $hasEnded,
            refused: null,
            installed: [],
            install: null,
        );
    }
}
