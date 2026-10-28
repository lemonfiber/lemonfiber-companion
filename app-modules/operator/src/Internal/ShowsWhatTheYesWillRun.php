<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen that asks before a verb and shows, before the yes, the command the yes will run.
 *
 * The command is the stack's: the verb being asked about is rehearsed by the
 * stack as soon as the question is put, and the line its rehearsal reports is
 * drawn above the yes once it arrives. Nothing is drawn until it has. Where
 * the rehearsal could not be read, that is said in place of the line, so it
 * is told apart from a verb with no command; the question stands either way,
 * because the line is something to read before agreeing rather than a
 * condition of agreeing.
 *
 * **It reads the using screen's own `$supervising` and `$storage`**, for the
 * reason {@see FollowsWhatTheVerbCameTo} gives, and lets go of a session the
 * stack refused through {@see LetsGoOfARefusedSession}, which the screen also uses.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ShowsWhatTheYesWillRun
{
    /** The handle of the rehearsal asked for the question on the screen, while it is still being worked out. */
    public ?string $rehearsalOfTheYes = null;

    /** What the stack's rehearsal said of the command the verb being asked about will run, once it said anything. */
    public ?TheLineTheYesRuns $willRun = null;

    /** The name the stack gave what its rehearsal offers, which the yes carries back, once the rehearsal answered. */
    public ?AnOffer $offered = null;

    abstract public function stack(): Stack;


    /** Ask the stack to rehearse the verb now being asked about, and take its answer if it is already there. */
    private function rehearseTheQuestion(AgreedTo $agreed): void
    {
        $this->forgetTheRehearsal();
        $stack = $this->stack();

        $handle = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AsText => $this->supervising->rehearsed($stack, $session, $agreed)->either(
                started: static fn(Job $job): AsText => AsText::of($job->shown()),
                met: function (Obstacle $why) use ($stack): AsText {
                    $this->letGoOfTheSession($why, $stack);
                    $this->willRun = TheLineTheYesRuns::unread();

                    return AsText::nothing();
                },
            ),
            notHeld: static fn(): AsText => AsText::nothing(),
        )->said;
        $this->rehearsalOfTheYes = $handle === '' ? null : $handle;

        $this->followTheRehearsal();
    }

    /** Ask after the rehearsal while it is being worked out, keeping the command once it is said. */
    private function followTheRehearsal(): void
    {
        $asked = $this->rehearsalOfTheYes;

        if ($asked === null) {
            return;
        }

        $stack = $this->stack();
        $became = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowAgreedWorkIsGoing => $this->supervising->whatBecameOf($stack, $session, Job::named($asked)),
            notHeld: static fn(): HowAgreedWorkIsGoing => HowAgreedWorkIsGoing::ended(),
        );

        // Held only while the stack is still working it out: a rehearsal that
        // finished, ended or could not be asked after has nothing left to read.
        $this->rehearsalOfTheYes = null;
        $said = $became->either(
            stillRunning: function () use ($asked): AsText {
                $this->rehearsalOfTheYes = $asked;

                return AsText::nothing();
            },
            // A report that was not a rehearsal is not a line the yes will run:
            // whatever it ran has already run, and saying it before the yes would
            // be saying it about the wrong thing.
            done: fn(WhatTheVerbCameTo $report): AsText|TheLineTheYesRuns => $report->was() === WhetherItWasRehearsed::Rehearsed
                ? $this->rehearsed($report)->whetherItRan(
                    ran: static fn(TheCommandLine $command): TheLineTheYesRuns => TheLineTheYesRuns::command($command->asTyped()),
                    declined: static fn(): AsText => AsText::nothing(),
                )
                : TheLineTheYesRuns::unread(),
            ended: static fn(): TheLineTheYesRuns => TheLineTheYesRuns::unread(),
            met: function (Obstacle $why) use ($stack): TheLineTheYesRuns {
                $this->letGoOfTheSession($why, $stack);

                return TheLineTheYesRuns::unread();
            },
            // A rehearsal answers no yes, so nothing it was given for can move;
            // a stack saying otherwise has said nothing readable.
            moved: static fn(): TheLineTheYesRuns => TheLineTheYesRuns::unread(),
        );
        $this->willRun = $said instanceof TheLineTheYesRuns ? $said : null;
    }

    /**
     * A rehearsal that answered: the name the stack gave what it offers is
     * kept, so the yes carries it back.
     */
    private function rehearsed(WhatTheVerbCameTo $report): WhatTheVerbCameTo
    {
        $this->offered = $report->offer();

        return $report;
    }

    /** Put away whatever was rehearsed for a question that is no longer on the screen. */
    private function forgetTheRehearsal(): void
    {
        $this->rehearsalOfTheYes = null;
        $this->willRun = null;
        $this->offered = null;
    }
}
