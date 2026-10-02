<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheImport;
use Modules\Kernel\Api\TheRecords;
use Modules\Kernel\Api\TheReplacement;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Kernel\Api\WhatWasNamed;
use Modules\Operator\Internal\ViewModels\ALineOfTheSurvey;
use Modules\Operator\Internal\ViewModels\AMoveAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheMoveTurnedOutToBe;

use function sprintf;

/**
 * Where asking to move in has got to, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state, each saying only its
 * own.
 *
 * **The stance is drawn as given.** Each of the four has its own sentence, and
 * only *applied* says anything was done. An import has three sentences of its
 * own, because an import that carried nothing and one that has not run are
 * both quiet and mean opposite things.
 *
 * **What did not come across leads.** It is a list of its own with its own
 * heading, drawn before the stance and before what did, so it is never
 * subordinate to a success.
 *
 * **What is copied first is said before the yes**, and only where there is a
 * yes to say it before: a pending answer to asking without one.
 */
final readonly class HowAMoveReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): TheMoveTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), '');
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(string $mode): TheMoveTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded(), $mode);
    }

    /** The stack is still working it out. */
    public function running(string $mode): TheMoveTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $mode, isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as refusing it. */
    public function ended(string $mode): TheMoveTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $mode, hasEnded: true);
    }

    /** The stack turned the request down, and this is its reason. */
    public function refused(string $because, string $mode): TheMoveTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $mode, refusal: $because);
    }

    /** Asking, or asking after it, met this instead. */
    public function met(Obstacle $why, string $mode): TheMoveTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why), $mode);
    }

    /** Where the move stands, and what it came to or would come to. */
    public function answered(AMove $move): TheMoveTurnedOutToBe
    {
        return new TheMoveTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            mode: $move->by()->value,
            isWorking: false,
            hasEnded: false,
            refusal: '',
            move: $move->either(
                adopting: fn(TheAdoption $adoption): AMoveAsShown => $this->adopting($move, $adoption),
                importing: fn(TheImport $import): AMoveAsShown => $this->importing($move, $import),
                standingBeside: fn(TheStandingBeside $beside): AMoveAsShown => $this->standingBeside($move, $beside),
                replacing: fn(TheReplacement $replacement): AMoveAsShown => $this->replacing($move, $replacement),
            ),
        );
    }

    /** Adopting: each service a newer version opens, and the copy taken before it does. */
    private function adopting(AMove $move, TheAdoption $adoption): AMoveAsShown
    {
        $lines = $this->project($adoption->project());
        $copyFirst = [];

        foreach ($adoption as $upgrade) {
            $what = $upgrade->what();
            $with = [
                'service' => $what->service(),
                'existing' => $upgrade->existing(),
                'ours' => $upgrade->ours(),
                'verdict' => $upgrade->verdict(),
                'because' => $what->because(),
            ];
            $lines[] = new ALineOfTheSurvey($what->isRefused() ? 'stacks.moving_in.upgrade_refused' : 'stacks.moving_in.upgrade', $with);

            if ($what->wantsACopyFirst()) {
                $copyFirst[] = new ALineOfTheSurvey('stacks.moving_in.copy_first', $with);
            }
        }

        if ($adoption->backedUp() !== '') {
            $lines[] = new ALineOfTheSurvey('stacks.moving_in.backed_up', ['path' => $adoption->backedUp()]);
        }

        foreach ($adoption->backUp() as $path) {
            $copyFirst[] = new ALineOfTheSurvey('stacks.moving_in.copies', ['path' => $path]);
        }

        return $this->shown($move, $lines, $copyFirst, $this->stance($move->stance()));
    }

    /** Importing: what could not come across first, then what did or would. */
    private function importing(AMove $move, TheImport $import): AMoveAsShown
    {
        $pending = $move->stance() === Stance::Pending;
        $leftBehind = [];

        foreach ($import->notCarried() as $limit) {
            $leftBehind[] = new ALineOfTheSurvey('stacks.moving_in.not_carried', ['what' => $limit->what(), 'because' => $limit->because()]);
        }

        return new AMoveAsShown(
            stanceSaid: match (true) {
                $pending => 'stacks.moving_in.import.not_run',
                $move->stance() === Stance::Unchanged => 'stacks.moving_in.import.nothing_to_carry',
                $move->stance() === Stance::Applied && $import->carried()->count() === 0 => 'stacks.moving_in.import.carried_nothing',
                default => $this->stance($move->stance()),
            },
            refusal: $move->refusal(),
            // An import turned away carried nothing and was not asked to, so
            // there is nothing it left behind to lead with.
            leftBehindSaid: match ($move->stance()) {
                Stance::Pending => 'stacks.moving_in.would_leave_behind',
                Stance::Blocked => '',
                Stance::Unchanged, Stance::Applied => 'stacks.moving_in.left_behind',
            },
            leftBehind: $leftBehind,
            lines: [
                ...$this->project($import->project()),
                ...($pending
                    ? $this->records('stacks.moving_in.would_carry', $import->wouldCarry())
                    : $this->records('stacks.moving_in.carried', $import->carried())),
            ],
            copyFirst: [],
            agreeSaid: $pending ? $this->agree(MovingInBy::Importing) : '',
        );
    }

    /** Standing beside: where each service listens instead, and the file that says so. */
    private function standingBeside(AMove $move, TheStandingBeside $beside): AMoveAsShown
    {
        $said = $move->stance() === Stance::Pending ? 'stacks.already_here.moved' : 'stacks.moving_in.listens';
        $lines = [];

        foreach ($beside->ports() as $moved) {
            $lines[] = new ALineOfTheSurvey($said, [
                'service' => $moved->service(),
                'from' => sprintf('%d', $moved->from()),
                'to' => sprintf('%d', $moved->to()),
            ]);
        }

        if ($beside->written() !== '') {
            $lines[] = new ALineOfTheSurvey('stacks.moving_in.written', ['path' => $beside->written()]);
        }

        return $this->shown($move, $lines, [], $this->stance($move->stance()));
    }

    /**
     * Replacing: what it would stop, or what would not stop and what it stopped.
     *
     * What would not stop leads, and a replacement the stack applied is not
     * said to be done while anything it replaces is still up.
     */
    private function replacing(AMove $move, TheReplacement $replacement): AMoveAsShown
    {
        $lines = $move->stance() === Stance::Pending
            ? $this->names('stacks.moving_in.would_stop', $replacement->wouldStop())
            : [
                ...$this->names('stacks.moving_in.still_running', $replacement->stillRunning()),
                ...$this->names('stacks.moving_in.stopped', $replacement->stopped()),
            ];
        $unfinished = $move->stance() === Stance::Applied && $replacement->leftSomethingRunning();

        return $this->shown(
            $move,
            [...$this->project($replacement->project()), ...$lines],
            [],
            $unfinished ? 'stacks.moving_in.stance.applied_still_running' : $this->stance($move->stance()),
        );
    }

    /**
     * A way of moving in that carries no records across, drawn with the yes offered only where it is pending.
     *
     * @param list<ALineOfTheSurvey> $lines
     * @param list<ALineOfTheSurvey> $copyFirst
     * @param string                 $stanceSaid the catalogue key the stance is drawn with
     */
    private function shown(AMove $move, array $lines, array $copyFirst, string $stanceSaid): AMoveAsShown
    {
        $pending = $move->stance() === Stance::Pending;

        return new AMoveAsShown(
            stanceSaid: $stanceSaid,
            refusal: $move->refusal(),
            leftBehindSaid: '',
            leftBehind: [],
            lines: $lines,
            copyFirst: $pending ? $copyFirst : [],
            agreeSaid: $pending ? $this->agree($move->by()) : '',
        );
    }

    /** The catalogue key for a stance, as the stack gave it. */
    private function stance(Stance $stance): string
    {
        return match ($stance) {
            Stance::Unchanged => 'stacks.moving_in.stance.unchanged',
            Stance::Pending => 'stacks.moving_in.stance.pending',
            Stance::Blocked => 'stacks.moving_in.stance.blocked',
            Stance::Applied => 'stacks.moving_in.stance.applied',
        };
    }

    /** The catalogue key for agreeing to one way of moving in. */
    private function agree(MovingInBy $by): string
    {
        return match ($by) {
            MovingInBy::Adopting => 'stacks.moving_in.agree.adopt',
            MovingInBy::Importing => 'stacks.moving_in.agree.import',
            MovingInBy::StandingBeside => 'stacks.moving_in.agree.beside',
            MovingInBy::Replacing => 'stacks.moving_in.agree.replace',
        };
    }

    /**
     * The project a move is about, where the stack named one.
     *
     * @return list<ALineOfTheSurvey>
     */
    private function project(string $project): array
    {
        return $project === '' ? [] : [new ALineOfTheSurvey('stacks.already_here.project', ['project' => $project])];
    }

    /**
     * One line per record, under one sentence.
     *
     * @return list<ALineOfTheSurvey>
     */
    private function records(string $said, TheRecords $records): array
    {
        $lines = [];

        foreach ($records as $record) {
            $lines[] = new ALineOfTheSurvey($said, ['service' => $record->service(), 'kind' => $record->kind(), 'name' => $record->name()]);
        }

        return $lines;
    }

    /**
     * One line per service, under one sentence.
     *
     * @return list<ALineOfTheSurvey>
     */
    private function names(string $said, WhatWasNamed $services): array
    {
        $lines = [];

        foreach ($services as $service) {
            $lines[] = new ALineOfTheSurvey($said, ['service' => $service]);
        }

        return $lines;
    }

    /** An answer with no move in it, for every state but one answered. */
    private function without(
        HowTheReadingWent $went,
        string $mode,
        bool $isWorking = false,
        bool $hasEnded = false,
        string $refusal = '',
    ): TheMoveTurnedOutToBe {
        return new TheMoveTurnedOutToBe(
            went: $went,
            mode: $mode,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            refusal: $refusal,
            move: null,
        );
    }
}
