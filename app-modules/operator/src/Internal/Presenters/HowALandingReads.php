<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\ViewModels\HowTheLandingWent;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/** Folds a change agreed to once the downloads land into what the row it changes draws. */
final readonly class HowALandingReads
{
    /** The key said where the stack stopped waiting before the downloads finished. */
    public const string ENDED = 'config.landing.ended';

    /** The key said where what the change was agreed against has moved. */
    public const string MOVED = 'config.landing.moved';

    /** Nothing waits. */
    public function nothingWaits(): HowTheLandingWent
    {
        return new HowTheLandingWent(WhereTheWorkStands::notAsked(), '', null, null);
    }

    /** The stack waits for the downloads before changing this setting. */
    public function waiting(string $key): HowTheLandingWent
    {
        return new HowTheLandingWent(WhereTheWorkStands::running(), $key, null, null);
    }

    /** The downloads landed, and the change came to this. */
    public function made(string $key, WhereTheChangeStands $stands): HowTheLandingWent
    {
        return new HowTheLandingWent(WhereTheWorkStands::answered(), $key, new HowAChangeReads()->stands($stands), null);
    }

    /** The stack stopped waiting before the downloads finished, and made no change. */
    public function ended(string $key, ?WhyTheWorkEnded $why): HowTheLandingWent
    {
        return new HowTheLandingWent(WhereTheWorkStands::ended($why), $key, null, self::ENDED);
    }

    /** What the change was agreed against has moved, and no change was made. */
    public function moved(string $key): HowTheLandingWent
    {
        return new HowTheLandingWent(WhereTheWorkStands::answered(), $key, null, self::MOVED);
    }

    /** What stood in the way of asking after the waiting. */
    public function met(string $key, Obstacle $why): HowTheLandingWent
    {
        return new HowTheLandingWent(WhereTheWorkStands::met($why), $key, new HowAChangeReads()->met($why), null);
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): HowTheLandingWent
    {
        return new HowTheLandingWent(WhereTheWorkStands::signedOut(), '', null, null);
    }
}
