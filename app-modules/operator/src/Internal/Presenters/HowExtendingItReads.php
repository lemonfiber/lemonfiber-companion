<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AnUpdate;
use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\APluginRemoval;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ExtendingIt;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\TheShapesTaken;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\APluginInstallAsShown;
use Modules\Operator\Internal\ViewModels\APluginRemovalAsShown;
use Modules\Operator\Internal\ViewModels\APluginUpdateAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatExtendsItTurnedOutToBe;

/**
 * The plugins on a stack, and installing, updating or removing one, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state, each saying only its
 * own, so a template never offers a yes beneath an account that is not a
 * reading, and never draws an act as done short of all of what makes it so.
 * What each account holds is {@see HowAPluginAccountReads}'s.
 *
 * **What the stack is at, and what it would not do, is said by the act.** A
 * rehearsal of any of the three reads as a rehearsal; after the yes, the act
 * agreed to names itself.
 */
final readonly class HowExtendingItReads
{
    /** This device no longer holds a session for the stack. */
    public function signedOut(): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    /** Asking met this instead. */
    public function met(Obstacle $why, bool $agreed): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why), afterTheYes: $agreed);
    }

    /** A source is being typed, and nothing has been asked. */
    public function typing(): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), typing: true);
    }

    /** The stack is at work on the rehearsal, or on the act agreed to. */
    public function running(ExtendingIt $act, bool $agreed): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), isWorking: true, afterTheYes: $agreed, workingSaid: $this->working($act, $agreed));
    }

    /** The stack has no outcome for the work any more, which is not the same as it not having happened. */
    public function ended(bool $agreed): WhatExtendsItTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), afterTheYes: $agreed, hasEnded: true);
    }

    /** The stack refused, and this is what it said and named, with each service the yes left unapproved to take its shape. */
    public function refused(ARefusalInItsWords $why, ExtendingIt $act, bool $agreed, TheShapesTaken $leftUnapproved): WhatExtendsItTurnedOutToBe
    {
        return new WhatExtendsItTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            typing: false,
            isWorking: false,
            afterTheYes: $agreed,
            workingSaid: '',
            hasEnded: false,
            refusedSaid: $this->refusal($act, $agreed),
            refused: new HowARefusalReads()->inItsWords($why),
            unapproved: new HowAPluginAccountReads()->leftUnapproved($leftUnapproved),
            installed: [],
            install: null,
            update: null,
            removal: null,
        );
    }

    /**
     * What the stack said of its plugins: the listing, and the account of the act it is about.
     *
     * @param list<string> $approved every value the operator has approved so far, as the reading spells each
     */
    public function answered(ThePlugins $plugins, array $approved, bool $agreed): WhatExtendsItTurnedOutToBe
    {
        $rows = new HowAPluginAccountReads();
        $installed = [];

        foreach ($plugins->installed() as $plugin) {
            $installed[] = $rows->plugin($plugin, $plugins->sourceOf($plugin), $approved, $plugins->answersOutOfContractOf($plugin));
        }

        $agreeable = $plugins->agreement() !== '';
        $account = $plugins->either(
            listed: static fn(): AsText => AsText::nothing(),
            install: static fn(APluginInstall $install): APluginInstallAsShown => $rows->install($install, $approved, $agreeable),
            update: static fn(AnUpdate $update): APluginUpdateAsShown => $rows->update($update, $approved, $agreeable),
            removal: static fn(APluginRemoval $removal): APluginRemovalAsShown => $rows->removal($removal, $agreeable),
        );

        return new WhatExtendsItTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            typing: false,
            isWorking: false,
            afterTheYes: $agreed,
            workingSaid: '',
            hasEnded: false,
            refusedSaid: '',
            refused: null,
            unapproved: [],
            installed: $installed,
            install: $account instanceof APluginInstallAsShown ? $account : null,
            update: $account instanceof APluginUpdateAsShown ? $account : null,
            removal: $account instanceof APluginRemovalAsShown ? $account : null,
        );
    }

    /** The catalogue key for what the stack is at: a rehearsal before the yes, the act itself after it. */
    private function working(ExtendingIt $act, bool $agreed): string
    {
        if (! $agreed) {
            return 'plugins.working.rehearse';
        }

        return match ($act) {
            ExtendingIt::Install => 'plugins.working.install',
            ExtendingIt::Update => 'plugins.working.update',
            ExtendingIt::Remove => 'plugins.working.remove',
        };
    }

    /** The catalogue key for what the stack would not do: rehearse before the yes, the act itself after it. */
    private function refusal(ExtendingIt $act, bool $agreed): string
    {
        if (! $agreed) {
            return 'plugins.refused.rehearse';
        }

        return match ($act) {
            ExtendingIt::Install => 'plugins.refused.install',
            ExtendingIt::Update => 'plugins.refused.update',
            ExtendingIt::Remove => 'plugins.refused.remove',
        };
    }

    /** A state with neither a listing nor an account in it. */
    private function without(
        HowTheReadingWent $went,
        bool $typing = false,
        bool $isWorking = false,
        bool $afterTheYes = false,
        string $workingSaid = '',
        bool $hasEnded = false,
    ): WhatExtendsItTurnedOutToBe {
        return new WhatExtendsItTurnedOutToBe(
            went: $went,
            typing: $typing,
            isWorking: $isWorking,
            afterTheYes: $afterTheYes,
            workingSaid: $workingSaid,
            hasEnded: $hasEnded,
            refusedSaid: '',
            refused: null,
            unapproved: [],
            installed: [],
            install: null,
            update: null,
            removal: null,
        );
    }
}
