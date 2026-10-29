<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_search;
use function in_array;
use function is_int;

use Modules\Kernel\Api\AnUpdate;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\APluginRemoval;
use Modules\Kernel\Api\APluginReproof;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\ARecipeRun;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\AShapeTaken;
use Modules\Kernel\Api\HowItsSourceStands;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheGrantsAsked;
use Modules\Kernel\Api\TheProofs;
use Modules\Kernel\Api\TheShapesTaken;
use Modules\Kernel\Api\TheUpstreamsWritten;
use Modules\Kernel\Api\WhatARecipeRunDid;
use Modules\Kernel\Api\WhenARecipeRuns;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\AChangeAndWhyAsShown;
use Modules\Operator\Internal\ViewModels\AContestAsShown;
use Modules\Operator\Internal\ViewModels\AGrantAskedAsShown;
use Modules\Operator\Internal\ViewModels\AnAnswerOutOfContractAsShown;
use Modules\Operator\Internal\ViewModels\AnUpstreamWrittenAsShown;
use Modules\Operator\Internal\ViewModels\APluginAsShown;
use Modules\Operator\Internal\ViewModels\APluginChangeAsShown;
use Modules\Operator\Internal\ViewModels\APluginInstallAsShown;
use Modules\Operator\Internal\ViewModels\APluginRemovalAsShown;
use Modules\Operator\Internal\ViewModels\APluginReproofAsShown;
use Modules\Operator\Internal\ViewModels\APluginUpdateAsShown;
use Modules\Operator\Internal\ViewModels\AProofAsShown;
use Modules\Operator\Internal\ViewModels\ARecipeAsShown;
use Modules\Operator\Internal\ViewModels\ARecipeRunAsShown;
use Modules\Operator\Internal\ViewModels\ARecipeStepAsShown;
use Modules\Operator\Internal\ViewModels\AShapeTakenAsShown;
use Modules\Operator\Internal\ViewModels\AStepThatRanAsShown;
use Modules\Operator\Internal\ViewModels\AValueCarriedAsShown;
use Modules\Operator\Internal\ViewModels\HowPuttingARunBackWent;

/**
 * One plugin, and the account of installing, updating or removing one, as the rows a template draws.
 *
 * Apart from {@see HowExtendingItReads}, which says where the screen has got
 * to: this says what each account holds. `F2`: data in, view model out.
 *
 * **A headline only after the yes.** A reading has none, and is labelled as
 * one by the template; afterwards each act says how it ended in its own
 * words, and *installed*, *updated* and *removed* are each said only where
 * the stack's account holds all of what makes them so.
 */
final readonly class HowAPluginAccountReads
{
    /**
     * One plugin, with every recipe in full.
     *
     * @param list<string>                $approved
     */
    public function plugin(APlugin $plugin, HowItsSourceStands $standing, array $approved, TheAnswersOutOfContract $outOfContract): APluginAsShown
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
            updatable: $plugin->canBeUpdated(),
            outOfContract: $this->outOfContract($outOfContract),
        );
    }

    /**
     * An install's account.
     *
     * @param list<string> $approved
     */
    public function install(APluginInstall $install, array $approved, bool $agreeable): APluginInstallAsShown
    {
        $changes = [];

        foreach ($install->changes() as $change) {
            $changes[] = new APluginChangeAsShown(path: $change->path(), putsSaid: $change->puts()->saidOnTheScreen());
        }

        $proofs = $this->proofs($install->proofs());

        $overrides = [];

        foreach ($install->overrides() as $override) {
            $overrides[] = new AChangeAndWhyAsShown(target: $override->setting(), because: $override->why());
        }

        $contests = [];

        foreach ($install->contests() as $contest) {
            $contests[] = new AContestAsShown(capability: $contest->capability(), by: $contest->by(), claimants: $this->lines($contest->claimants()));
        }

        $approvals = $this->lines($install->approvals());
        $taking = [];

        foreach ($install->taking() as $taken) {
            $place = array_search($taken->approval(), $approvals, strict: true);

            $taking[] = $this->shape($taken, is_int($place) ? $place : null, in_array($taken->approval(), $approved, strict: true));
        }

        $checks = $install->checks();
        $putBack = $install->wasItPutBack(
            putBack: static fn(ARunPutBack $report): HowPuttingARunBackWent => new HowARunBackReads()->done($report),
            notPutBack: static fn(): AsText => AsText::nothing(),
        );

        return new APluginInstallAsShown(
            plugin: $this->plugin($install->would(), HowItsSourceStands::notSaid(), $approved, TheAnswersOutOfContract::these()),
            isAReading: $install->isAReading(),
            agreeable: $agreeable,
            headline: $this->headline($install),
            changes: $changes,
            proofs: $proofs,
            overrides: $overrides,
            contests: $contests,
            taking: $taking,
            granting: $this->granting($install->granting(), $approvals, $approved),
            sharing: $this->sharing($install->sharing()),
            checked: $checks->wereAsked(),
            broke: $this->lines($checks->broke()),
            unsettled: $this->lines($checks->unsettled()),
            putBack: $putBack instanceof HowPuttingARunBackWent ? $putBack : null,
        );
    }

    /**
     * An update's account: from and to, what stops, the new version's install, and what putting the old one back came to.
     *
     * @param list<string> $approved
     */
    public function update(AnUpdate $update, array $approved, bool $agreeable): APluginUpdateAsShown
    {
        $restored = $update->restored();

        return new APluginUpdateAsShown(
            isAReading: $update->isAReading(),
            from: $update->versions()->from(),
            to: $update->versions()->to(),
            interrupts: $this->lines($update->interrupts()),
            install: $this->install($update->install(), $approved, $agreeable),
            wentBack: new HowARunBackReads()->done($update->wentBack()),
            headline: match (true) {
                $update->isAReading() => '',
                $update->held() => 'plugins.updated_it',
                default => 'plugins.not_updated',
            },
            stopped: $update->stopped(),
            restoredSaid: match (true) {
                ! $restored->wasNeeded() => '',
                $restored->isPlaced() && $restored->isRunning() => 'plugins.restored.running',
                $restored->isPlaced() => 'plugins.restored.not_running',
                default => 'plugins.restored.not_placed',
            },
            restoredVersion: $restored->version(),
        );
    }

    /** A removal's account: what stops and what is left unfilled, and how far it went after the yes. */
    public function removal(APluginRemoval $removal, bool $agreeable): APluginRemovalAsShown
    {
        $leaves = [];

        foreach ($removal->leaves() as $unfilled) {
            $leaves[] = new AChangeAndWhyAsShown(target: $unfilled->capability(), because: $unfilled->filledBy());
        }

        return new APluginRemovalAsShown(
            plugin: $removal->plugin(),
            isAReading: $removal->isAReading(),
            agreeable: $agreeable,
            headline: match (true) {
                $removal->isAReading() => '',
                $removal->wasRemoved() => 'plugins.removed_it',
                default => 'plugins.partly_removed',
            },
            interrupts: $this->lines($removal->interrupts()),
            leaves: $leaves,
            wentBack: new HowARunBackReads()->done($removal->wentBack()),
        );
    }

    /** What proving a plugin again would ask, or came to; the kept answers say which capability a pass would clear. */
    public function reproof(APluginReproof $proof, bool $agreeable): APluginReproofAsShown
    {
        return new APluginReproofAsShown(
            plugin: $proof->plugin(),
            isAReading: $proof->isAReading(),
            agreeable: $agreeable,
            headline: match (true) {
                $proof->isAReading() => 'plugins.proof_reading',
                $proof->wasCleared() => 'plugins.proved_it',
                default => 'plugins.not_proved',
            },
            proofs: $this->proofs($proof->proofs()),
            kept: $this->outOfContract($proof->kept()),
        );
    }

    /**
     * Running a demand recipe: the rehearsal, each pair with its switch, or what each step came to.
     *
     * @param list<string> $approved
     */
    public function run(ARecipeRun $run, array $approved, bool $agreeable): ARecipeRunAsShown
    {
        $recipe = $this->recipe($run->recipe(), $this->lines($run->recipe()->approvals()), $approved);

        return $run->either(
            rehearsed: static fn(): ARecipeRunAsShown => new ARecipeRunAsShown(
                plugin: $run->plugin()->named(),
                isAReading: true,
                agreeable: $agreeable,
                headline: 'plugins.run.reading',
                recipe: $recipe,
                why: '',
                ran: [],
            ),
            ran: static function (WhatARecipeRunDid $did) use ($run, $recipe): ARecipeRunAsShown {
                $ran = [];

                foreach ($did->steps() as $step) {
                    $ran[] = new AStepThatRanAsShown(step: $step->step(), to: $step->to(), cameSaid: $step->came()->saidOnTheScreen(), why: $step->why());
                }

                return new ARecipeRunAsShown(
                    plugin: $run->plugin()->named(),
                    isAReading: false,
                    agreeable: false,
                    headline: $did->held() ? 'plugins.run.held' : 'plugins.run.not_held',
                    recipe: $recipe,
                    why: $did->why(),
                    ran: $ran,
                );
            },
        );
    }

    /**
     * Each service left unapproved to take its privileged shape, as a refusal names it.
     *
     * @return list<AShapeTakenAsShown>
     */
    public function leftUnapproved(TheShapesTaken $left): array
    {
        $shown = [];

        foreach ($left as $taken) {
            $shown[] = $this->shape($taken, approval: null, approved: false);
        }

        return $shown;
    }

    /**
     * Every credential of another service the install would hand the plugin, each with its switch where the install offers one.
     *
     * @param  list<string>             $approvals every approval the install asks for
     * @param  list<string>             $approved  every approval the operator has given
     * @return list<AGrantAskedAsShown>
     */
    private function granting(TheGrantsAsked $granting, array $approvals, array $approved): array
    {
        $shown = [];

        foreach ($granting as $asked) {
            $place = array_search($asked->approval(), $approvals, strict: true);
            $grant = $asked->grant();

            $shown[] = new AGrantAskedAsShown(
                credential: $grant->credential(),
                whose: $grant->whose()->named(),
                fills: $grant->fills(),
                approval: is_int($place) ? $place : null,
                approved: in_array($asked->approval(), $approved, strict: true),
            );
        }

        return $shown;
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
                asksForApproval: $pair->asksForApproval(),
                approval: is_int($place) ? $place : null,
                approved: $pair->asksForApproval() && in_array($pair->approval(), $approved, strict: true),
            );
        }

        $asks = [];

        foreach ($recipe->asks() as $input) {
            $asks[] = $input->ask();
        }

        return new ARecipeAsShown(title: $recipe->title(), why: $recipe->why(), steps: $steps, pairs: $pairs, onDemand: $recipe->runs() === WhenARecipeRuns::Demand, asks: $asks);
    }

    /** One service taking a privileged shape, with its switch's place where the screen offers one. */
    private function shape(AShapeTaken $taken, ?int $approval, bool $approved): AShapeTakenAsShown
    {
        return new AShapeTakenAsShown(
            service: $taken->service(),
            neededFor: $taken->shape()->neededFor(),
            grants: $this->lines($taken->grants()),
            devices: $this->lines($taken->devices()),
            approval: $approval,
            approved: $approved,
        );
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

    /**
     * Each proof, what it asks, what it establishes, and what it came to.
     *
     * @return list<AProofAsShown>
     */
    private function proofs(TheProofs $proofs): array
    {
        $shown = [];

        foreach ($proofs as $proof) {
            $shown[] = new AProofAsShown(
                establishes: $proof->establishes(),
                asks: $proof->asks(),
                why: $proof->why(),
                cameToSaid: $proof->cameTo()->says()->saidOnTheScreen(),
                said: $this->lines($proof->cameTo()->said()),
            );
        }

        return $shown;
    }

    /**
     * Each answer out of contract, as the plugin's card says it.
     *
     * @return list<AnAnswerOutOfContractAsShown>
     */
    private function outOfContract(TheAnswersOutOfContract $answers): array
    {
        $shown = [];

        foreach ($answers as $answer) {
            $shown[] = new AnAnswerOutOfContractAsShown($answer->capability(), $answer->operation(), $answer->why());
        }

        return $shown;
    }

    /**
     * Each adapter that would write the files of the service it fronts.
     *
     * @return list<AnUpstreamWrittenAsShown>
     */
    private function sharing(TheUpstreamsWritten $sharing): array
    {
        $shown = [];

        foreach ($sharing as $written) {
            $shown[] = new AnUpstreamWrittenAsShown(adapter: $written->adapter(), upstream: $written->upstream(), at: $written->at());
        }

        return $shown;
    }
}
