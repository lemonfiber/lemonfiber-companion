<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\NamedOnTheManifest;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatItReaches;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatWasLeftBehind;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\OneLineItReachesAsShown;
use Modules\Operator\Internal\ViewModels\SomethingComingAsShown;
use Modules\Operator\Internal\ViewModels\SomethingNotOursAsShown;
use Modules\Operator\Internal\ViewModels\SomethingOutsideAsShown;
use Modules\Operator\Internal\ViewModels\TakingItOffTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheReadingAsShown;
use Modules\Operator\Internal\ViewModels\WhatTakingItOffDidAsShown;

/**
 * Where taking lemonfiber off has got to, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state, each saying only its
 * own, so a template never offers a yes beneath a removal already agreed to,
 * never draws a partial removal as complete, and never draws a reading that
 * could not read everything as a whole machine.
 */
final readonly class HowTakingItOffReads
{
    /** The four removals are in front of the operator to choose from, and none is being read. */
    public function notChosen(): TakingItOffTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), chosen: false);
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): TakingItOffTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    /** Reading the removal met this, and nothing was agreed to. */
    public function met(Obstacle $why): TakingItOffTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack is taking it off. */
    public function running(bool $endsThisSession): TakingItOffTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), wasAgreed: true, isWorking: true, endsThisSession: $endsThisSession);
    }

    /** The stack has no outcome for the yes any more, which is not the same as it not having happened. */
    public function ended(bool $endsThisSession): TakingItOffTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), wasAgreed: true, hasEnded: true, endsThisSession: $endsThisSession);
    }

    /** The stack refused the yes, and this is its reason. */
    public function refused(string $because): TakingItOffTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), wasAgreed: true, refusal: $because);
    }

    /**
     * Following the yes met this, so whether it happened could not be read.
     *
     * Where the removal takes what admits this app, that is what an answer
     * going missing looks like: the stack may no longer answer this app at
     * all. It is said as that, and never as a credential refused.
     */
    public function unreadAfterTheYes(Obstacle $why, bool $endsThisSession): TakingItOffTurnedOutToBe
    {
        return $endsThisSession
            ? $this->without(HowTheReadingWent::itCameBack(), wasAgreed: true, endsThisSession: true)
            : $this->without(HowTheReadingWent::somethingStopped($why), wasAgreed: true);
    }

    /** What the stack answered: the reading, and what the removal did where it was agreed to. */
    public function answered(AnUninstall $uninstall, bool $agreed): TakingItOffTurnedOutToBe
    {
        $manifest = $uninstall->manifest();
        $did = $uninstall->removal()->either(
            surveyed: static fn(): AsText => AsText::nothing(),
            rehearsed: static fn(): WhatTakingItOffDidAsShown => new WhatTakingItOffDidAsShown('uninstall.did.rehearsed', isFinished: false, gone: [], credentials: [], left: []),
            complete: fn(NamedOnTheManifest $gone, NamedOnTheManifest $credentials): WhatTakingItOffDidAsShown
                => new WhatTakingItOffDidAsShown('uninstall.did.complete', isFinished: true, gone: $this->names($gone), credentials: $this->names($credentials), left: []),
            partial: fn(NamedOnTheManifest $gone, NamedOnTheManifest $credentials, WhatWasLeftBehind $left): WhatTakingItOffDidAsShown
                => new WhatTakingItOffDidAsShown('uninstall.did.partial', isFinished: true, gone: $this->names($gone), credentials: $this->names($credentials), left: $this->left($left)),
        );

        $did = $did instanceof WhatTakingItOffDidAsShown ? $did : null;

        return new TakingItOffTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            chosen: true,
            wasAgreed: $agreed,
            isWorking: false,
            hasEnded: false,
            refusal: '',
            endsThisSession: $did?->isFinished === true && $manifest->tier()->takesWhatAdmitsThisApp(),
            reading: $this->reading($manifest),
            did: $did,
        );
    }

    /** The reading, as the rows that draw it. */
    private function reading(WhatTakingItOffComesTo $manifest): TheReadingAsShown
    {
        $foreign = [];

        foreach ($manifest->foreign() as $found) {
            $foreign[] = new SomethingNotOursAsShown(at: $found->where(), files: $found->files(), size: $this->size($found->bytes()));
        }

        $coming = [];

        foreach ($manifest->coming() as $download) {
            $coming[] = new SomethingComingAsShown(name: $download->name(), progress: $download->progress());
        }

        $outside = [];

        foreach ($manifest->outside() as $thing) {
            $outside[] = new SomethingOutsideAsShown(
                what: $thing->what(),
                why: $thing->why(),
                byHand: $thing->byHand(),
                foundSaid: $thing->wasFound() ? 'uninstall.was_found' : 'uninstall.not_found',
            );
        }

        $unread = [];

        foreach ($manifest->confidence() as $sentence) {
            $unread[] = $sentence;
        }

        return new TheReadingAsShown(
            tierSaid: $manifest->tier()->saidOnTheScreen(),
            agreeSaid: $manifest->tier()->agreedToAs(),
            takesTheLibrary: $manifest->tier()->takesTheLibrary(),
            endsThisSession: $manifest->tier()->takesWhatAdmitsThisApp(),
            removes: $manifest->removes(),
            keeps: $manifest->keeps(),
            isComplete: $manifest->confidence()->isComplete(),
            unread: $unread,
            going: $this->lines($manifest->items(), kept: false),
            kept: $this->lines($manifest->items(), kept: true),
            frees: $this->size($manifest->bytes()),
            foreign: $foreign,
            coming: $coming,
            outside: $outside,
            volume: $manifest->volume(),
            copyFirst: $manifest->copyFirst(),
        );
    }

    /**
     * The lines that go, or the lines kept, each as the row that draws it.
     *
     * @return list<OneLineItReachesAsShown>
     */
    private function lines(WhatItReaches $items, bool $kept): array
    {
        $lines = [];

        foreach ($items as $item) {
            if ($item->isKept() !== $kept) {
                continue;
            }

            $lines[] = new OneLineItReachesAsShown(
                name: $item->name(),
                sortSaid: $item->sort()->saidOnTheScreen(),
                what: $item->what(),
                holdsACredential: $item->holdsACredential(),
                size: $this->sizeIfRead($item->size()),
                whyKept: $item->whyItIsKept(),
            );
        }

        return $lines;
    }

    /**
     * Names a removal reported.
     *
     * @return list<string>
     */
    private function names(NamedOnTheManifest $named): array
    {
        $names = [];

        foreach ($named as $name) {
            $names[] = $name;
        }

        return $names;
    }

    /**
     * What a removal left, as rows.
     *
     * @return list<SomethingOutsideAsShown>
     */
    private function left(WhatWasLeftBehind $left): array
    {
        $rows = [];

        foreach ($left as $thing) {
            $rows[] = new SomethingOutsideAsShown(what: $thing->name(), why: $thing->why(), byHand: $thing->byHand(), foundSaid: '');
        }

        return $rows;
    }

    /** A size the stack may not have read, as a figure and a unit, or nothing. */
    private function sizeIfRead(AnAmountOfRoom $amount): ?ASizeAsShown
    {
        $shown = $amount->either(
            known: fn(int $bytes): ASizeAsShown => $this->size($bytes),
            unread: static fn(): AsText => AsText::nothing(),
        );

        return $shown instanceof ASizeAsShown ? $shown : null;
    }

    /** Bytes, as a figure and a unit. */
    private function size(int $bytes): ASizeAsShown
    {
        $big = HowBig::of($bytes);

        return new ASizeAsShown(figure: $big->figure, unit: $big->said);
    }

    /** A state with neither a reading nor a removal in it. */
    private function without(
        HowTheReadingWent $went,
        bool $chosen = true,
        bool $wasAgreed = false,
        bool $isWorking = false,
        bool $hasEnded = false,
        string $refusal = '',
        bool $endsThisSession = false,
    ): TakingItOffTurnedOutToBe {
        return new TakingItOffTurnedOutToBe(
            went: $went,
            chosen: $chosen,
            wasAgreed: $wasAgreed,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            refusal: $refusal,
            endsThisSession: $endsThisSession,
            reading: null,
            did: null,
        );
    }
}
