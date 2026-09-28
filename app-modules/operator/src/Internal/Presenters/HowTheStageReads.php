<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Health\Api\WhatTheWalkSaidSoFar;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\TheStageAsShown;

/**
 * The stage a running walk is at, as the fields a template draws.
 *
 * `F2`: data in, view model out. The stage is the stack's own word, handed on
 * as it came, and never a proportion of anything: a walk says what it is doing,
 * and a bar that filled would say only that it was doing something.
 *
 * **A stage that is not current is not where the walk is.** Whatever it said, it
 * said it before the subscription broke or went quiet, so it is handed on with
 * when it was heard, and the template says the stage could not be heard before
 * it says what was last true.
 */
final readonly class HowTheStageReads
{
    public function of(WhatTheWalkSaidSoFar $heard, Instant $now): TheStageAsShown
    {
        $listening = $heard->isListening();

        return $heard->step(
            none: static fn(): TheStageAsShown => new TheStageAsShown('', '', '', AgoAsShown::live(), $listening),
            current: fn(ALineItSaid $line): TheStageAsShown => $this->at($line, AgoAsShown::live(), $listening),
            asOf: fn(ALineItSaid $line, Instant $at): TheStageAsShown
                => $this->at($line, AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now), $listening),
        );
    }

    private function at(ALineItSaid $line, AgoAsShown $ago, bool $listening): TheStageAsShown
    {
        return new TheStageAsShown(
            $line->step()->value,
            $line->said(),
            $line->detail(
                said: static fn(string $detail): AsText => AsText::of($detail),
                nothing: static fn(): AsText => AsText::nothing(),
            )->said,
            $ago,
            $listening,
        );
    }
}
