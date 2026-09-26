<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhereABundleIs;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\ABundleAsShown;
use Modules\Operator\Internal\ViewModels\APieceAsShown;
use Modules\Operator\Internal\ViewModels\HowTheBundleWent;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

/**
 * What became of a support bundle asked for, as the template draws it.
 *
 * One method per state of following it, each saying only its own state. The
 * bundle is carried whole and as the stack wrote it: every file, every name it
 * reveals and everything it could not collect.
 */
final readonly class HowABundleReads
{
    /** Nothing was asked for from this screen, so there is nothing to follow. */
    public function notAsked(): HowTheBundleWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), wasAsked: false);
    }

    /** This device no longer holds a session for the stack the bundle was asked of. */
    public function signedOut(): HowTheBundleWent
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** Asking for it, or asking after it, met this instead. */
    public function met(Obstacle $why): HowTheBundleWent
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack is still gathering it. */
    public function running(): HowTheBundleWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(): HowTheBundleWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** The stack refused the bundle, and this is what it said. */
    public function refused(string $said): HowTheBundleWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), refused: $said);
    }

    /** The bundle, described or written, as the stack answered with it. */
    public function done(ABundle $bundle): HowTheBundleWent
    {
        $pieces = [];

        foreach ($bundle->pieces() as $piece) {
            $pieces[] = new APieceAsShown(name: $piece->name(), body: $piece->body());
        }

        $revealed = [];

        foreach ($bundle->terms()->revealed() as $setting) {
            $revealed[] = $setting->name();
        }

        return new HowTheBundleWent(
            went: HowTheReadingWent::itCameBack(),
            wasAsked: true,
            isWorking: false,
            hasEnded: false,
            refused: '',
            isWritten: $bundle->where()->isWritten(),
            bundle: new ABundleAsShown(
                bytes: $bundle->bytes(),
                whereSaid: $this->whereSaid($bundle->where()),
                where: $this->where($bundle->where()),
                window: $bundle->terms()->window(),
                filenamesSaid: $bundle->terms()->filenames() === WhatFilenamesShow::Shown
                    ? 'stacks.help.filenames_shown'
                    : 'stacks.help.filenames_replaced',
                revealed: $revealed,
                pieces: $pieces,
                missing: [...$bundle->missing()],
                takenAt: $bundle->taken()->moment(),
                lemonfiber: $bundle->taken()->lemonfiber(),
                stack: $bundle->taken()->stack(),
            ),
        );
    }

    /** The catalogue key for where the bundle went, or would go. */
    private function whereSaid(WhereABundleIs $where): string
    {
        return $where->either(
            wouldGo: static fn(): AsText => AsText::of('stacks.help.would_go'),
            written: static fn(): AsText => AsText::of('stacks.help.written_at'),
            unsaid: static fn(): AsText => AsText::of('stacks.help.would_go_unsaid'),
        )->said;
    }

    /** The path the bundle went to, or would go to, blank where the stack named none. */
    private function where(WhereABundleIs $where): string
    {
        return $where->either(
            wouldGo: AsText::of(...),
            written: AsText::of(...),
            unsaid: AsText::nothing(...),
        )->said;
    }

    /** A state with no bundle to draw. */
    private function following(
        HowTheReadingWent $went,
        bool $wasAsked = true,
        bool $isWorking = false,
        bool $hasEnded = false,
        string $refused = '',
    ): HowTheBundleWent {
        return new HowTheBundleWent(
            went: $went,
            wasAsked: $wasAsked,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            refused: $refused,
            isWritten: false,
            bundle: null,
        );
    }
}
