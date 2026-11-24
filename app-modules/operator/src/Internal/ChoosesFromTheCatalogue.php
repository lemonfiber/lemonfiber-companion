<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_key_exists;

use Modules\Kernel\Api\ACatalogueEntry;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\ASettingThatReaches;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\ThePluginCatalogue;
use Modules\Operator\Internal\Presenters\HowThePluginCatalogueReads;
use Modules\Operator\Internal\ViewModels\ThePluginCatalogueAsShown;

/**
 * Choosing a plugin to install from the stack's catalogue, and turning reaching the catalogue on where it is off.
 *
 * **The stack is the one asked.** The phone fetches no catalogue and checks
 * no signature; it lists what the stack verified, read on a frame after the
 * screen's own reading.
 *
 * **Choosing is the typed name's path.** The entry's id goes into the source
 * and the install is rehearsed as a typed one is; only the rehearsal's yes
 * installs.
 *
 * **Reach is turned on through the stack's review.** The key and value are
 * the ones the catalogue named, written by `config-set` as every config key
 * is: rehearsed, shown, then agreed to. The screen's own `agree()` is the
 * plugin act's, so the change's is `agreeToTheChange()`.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\ExtendingTheStack}
 * as `$extending`, {@see \Modules\Kernel\Api\Adjusting} as `$adjusting`,
 * {@see \Modules\Kernel\Api\SettingWhatServicesRead} as `$tuning`, and
 * follows its own act through {@see FollowsAJob} as {@see PluginActFollowed::TheAct},
 * reading where it stands through its `answer()`, and draws a frame at a time
 * ({@see \Modules\Wayfinding\Api\Screens\DrawsItsTemplateAFrameAtATime}).
 */
trait ChoosesFromTheCatalogue
{
    use ProposesAChange {
        agree as agreeToTheChange;
        whatBecameOfIt as whatBecameOfTheChangeFollowed;
        whileNothingIsFollowed as whileNoChangeIsFollowed;
        withoutASession as withoutASessionForTheChange;
    }

    /** The catalogue as drawn, once a frame has read it. Public for {@see Screens\WhatIsRunningHere::$answered}'s reason. */
    public ?ThePluginCatalogueAsShown $catalogued = null;

    /** @var list<ACatalogueEntry> each entry the catalogue last listed, which choosing picks from */
    public array $listedToChoose = [];

    /** The setting that turns reaching the catalogue on, while the stack says it is off. */
    public ?ASettingThatReaches $reaching = null;

    /** Whether the catalogue waits for the next frame because a tap read the stack on this one. */
    public bool $catalogueWaits = false;

    /** The catalogue, read once on a frame of its own, and not while an act is followed. */
    public function catalogue(): ThePluginCatalogueAsShown
    {
        if ($this->catalogued instanceof ThePluginCatalogueAsShown) {
            return $this->catalogued;
        }

        if ($this->catalogueWaits) {
            $this->catalogueWaits = false;
            $this->readsItsStack();
        }

        if ($this->awaitsAnOutcome(PluginActFollowed::TheAct) || ! $this->mayReadItsStack()) {
            return new HowThePluginCatalogueReads()->reading();
        }

        $stack = $this->stack();

        return $this->catalogued = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): ThePluginCatalogueAsShown => $this->extending->catalogueOf($stack, $session)->either(
                found: fn(ThePluginCatalogue $catalogue): ThePluginCatalogueAsShown => $this->held($catalogue, $this->listening->clock->now()),
                met: function (Obstacle $why) use ($stack): ThePluginCatalogueAsShown {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowThePluginCatalogueReads()->met($why);
                },
            ),
            notHeld: static fn(): ThePluginCatalogueAsShown => new HowThePluginCatalogueReads()->reading(),
        );
    }

    /** Read the catalogue again on the next frame, because the operator said so, leaving the source being typed as it is. */
    public function catalogueAgain(): void
    {
        $this->catalogued = null;
    }

    /**
     * Rehearse installing the entry in that place in the catalogue, as a typed name is rehearsed.
     *
     * A place it does not have, or an entry installed here already, chooses nothing.
     */
    public function choose(int $which): void
    {
        if (! array_key_exists($which, $this->listedToChoose) || $this->listedToChoose[$which]->installed() !== '') {
            return;
        }

        $this->source = APluginSource::listed($this->listedToChoose[$which])->said();
        $this->rehearse();
    }

    protected function seedFor(string $key): ?string
    {
        return $this->mayBeChanged($key) ? $this->reaching?->value() : null;
    }

    protected function mayBeChanged(string $key): bool
    {
        return $this->reaching instanceof ASettingThatReaches && $this->reaching->key() === $key;
    }

    protected function keptAs(string $key): ?string
    {
        return null;
    }

    protected function readAgainAfterAChange(): void
    {
        $this->catalogued = null;
        $this->catalogueWaits = true;
    }

    /** What the stack answered, held for choosing and for turning reach on, as drawn at the frame's moment. */
    private function held(ThePluginCatalogue $catalogue, Instant $now): ThePluginCatalogueAsShown
    {
        $shown = new HowThePluginCatalogueReads()->answered($catalogue, $now);

        return $catalogue->either(
            listed: function (Instant $published, int $serial, int $unreadable, array $entries) use ($shown): ThePluginCatalogueAsShown {
                $this->listedToChoose = $entries;
                $this->reaching = null;

                return $shown;
            },
            switchedOff: function (ASettingThatReaches $setting) use ($shown): ThePluginCatalogueAsShown {
                $this->listedToChoose = [];
                $this->reaching = $setting;

                return $shown;
            },
            unreached: function () use ($shown): ThePluginCatalogueAsShown {
                $this->listedToChoose = [];
                $this->reaching = null;

                return $shown;
            },
        );
    }
}
