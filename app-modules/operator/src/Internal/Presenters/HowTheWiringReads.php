<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AConnection;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\WhereAConnectionStands;
use Modules\Operator\Internal\ViewModels\AConnectionAsShown;
use Modules\Operator\Internal\ViewModels\ALineOfTheSurvey;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheWiringAsShown;
use Modules\Operator\Internal\ViewModels\TheWiringTurnedOutToBe;

/**
 * Where a wiring run has got to, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state, each saying only its
 * own.
 *
 * **Each connection is drawn in its own state.** Thirteen sentences for
 * thirteen answers, so *skipped* never reads as failed, and a value the
 * operator changed reads as kept rather than as wired or as something to put
 * back. The stack's words — a reason, or a service's rejection — are carried
 * beside the sentence as they came.
 */
final readonly class HowTheWiringReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): TheWiringTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack());
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): TheWiringTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    /** The stack is still carrying the run out. */
    public function running(): TheWiringTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as refusing it. */
    public function ended(): TheWiringTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** The stack turned the request down, and this is its reason. */
    public function refused(string $because): TheWiringTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), refusal: $because);
    }

    /** Asking, or asking after it, met this instead. */
    public function met(Obstacle $why): TheWiringTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    /** What the run came to. */
    public function answered(TheWiring $wiring): TheWiringTurnedOutToBe
    {
        $unsupported = [];

        foreach ($wiring->unsupported() as $limit) {
            $unsupported[] = new ALineOfTheSurvey('stacks.already_here.unsupported', ['what' => $limit->what(), 'because' => $limit->because()]);
        }

        $connections = [];

        foreach ($wiring as $connection) {
            $connections[] = $this->connection($connection);
        }

        return new TheWiringTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            refusal: '',
            wiring: new TheWiringAsShown(
                rehearsed: $wiring->wasRehearsed(),
                judgedSaid: match ($wiring->judged()) {
                    HowDriftWasJudged::Assessed => 'stacks.wiring.assessed',
                    HowDriftWasJudged::Unassessable => 'stacks.wiring.unassessable',
                },
                unsupported: $unsupported,
                connections: $connections,
            ),
        );
    }

    /** One connection, in its own state, with the stack's words and what it breaks. */
    private function connection(AConnection $connection): AConnectionAsShown
    {
        $ended = $connection->ended();

        return new AConnectionAsShown(
            connection: $connection->connection(),
            stateSaid: $this->state($ended->state()),
            said: $ended->said(),
            ours: $ended->ours(),
            yours: $ended->yours(),
            breakage: $connection->breaks()->breakage(),
            remediation: $connection->breaks()->remediation(),
        );
    }

    /** The catalogue key for how one connection turned out. */
    private function state(WhereAConnectionStands $state): string
    {
        return match ($state) {
            WhereAConnectionStands::Wired => 'stacks.wiring.state.wired',
            WhereAConnectionStands::AlreadyWired => 'stacks.wiring.state.already_wired',
            WhereAConnectionStands::Drifted => 'stacks.wiring.state.drifted',
            WhereAConnectionStands::Stale => 'stacks.wiring.state.stale',
            WhereAConnectionStands::Conflicted => 'stacks.wiring.state.conflicted',
            WhereAConnectionStands::Adopted => 'stacks.wiring.state.adopted',
            WhereAConnectionStands::Unmanaged => 'stacks.wiring.state.unmanaged',
            WhereAConnectionStands::WouldWire => 'stacks.wiring.state.would_wire',
            WhereAConnectionStands::WouldAdopt => 'stacks.wiring.state.would_adopt',
            WhereAConnectionStands::Observed => 'stacks.wiring.state.observed',
            WhereAConnectionStands::Skipped => 'stacks.wiring.state.skipped',
            WhereAConnectionStands::Failed => 'stacks.wiring.state.failed',
            WhereAConnectionStands::Refused => 'stacks.wiring.state.refused',
        };
    }

    /** An answer with no run in it, for every state but one answered. */
    private function without(HowTheReadingWent $went, bool $isWorking = false, bool $hasEnded = false, string $refusal = ''): TheWiringTurnedOutToBe
    {
        return new TheWiringTurnedOutToBe(
            went: $went,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            refusal: $refusal,
            wiring: null,
        );
    }
}
