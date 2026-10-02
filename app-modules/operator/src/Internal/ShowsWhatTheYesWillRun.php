<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\HowTheVerbIsGoing;
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
 * drawn above the yes once it arrives. Nothing is drawn until it has, and
 * nothing where the stack could not rehearse it or would not be reached: the
 * question stands without the line, because the line is something to read
 * before agreeing rather than a condition of agreeing.
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

    /** The command the verb being asked about will run, as the stack's rehearsal said it, or empty until it has. */
    public string $willRun = '';

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
            held: fn(Session $session): HowTheVerbIsGoing => $this->supervising->whatBecameOf($stack, $session, Job::named($asked)),
            notHeld: static fn(): HowTheVerbIsGoing => HowTheVerbIsGoing::ended(),
        );

        // Held only while the stack is still working it out: a rehearsal that
        // finished, ended or could not be asked after has nothing left to read.
        $this->rehearsalOfTheYes = null;
        $this->willRun = $became->either(
            stillRunning: function () use ($asked): AsText {
                $this->rehearsalOfTheYes = $asked;

                return AsText::nothing();
            },
            done: static fn(WhatTheVerbCameTo $report): AsText => $report->was() === WhetherItWasRehearsed::Rehearsed
                ? $report->whetherItRan(
                    ran: static fn(TheCommandLine $command): AsText => AsText::of($command->asTyped()),
                    declined: static fn(): AsText => AsText::nothing(),
                )
                // A report that was not a rehearsal is not a line the yes will run:
                // whatever it ran has already run, and saying it before the yes
                // would be saying it about the wrong thing.
                : AsText::nothing(),
            ended: static fn(): AsText => AsText::nothing(),
            met: function (Obstacle $why) use ($stack): AsText {
                $this->letGoOfTheSession($why, $stack);

                return AsText::nothing();
            },
        )->said;
    }

    /** Put away whatever was rehearsed for a question that is no longer on the screen. */
    private function forgetTheRehearsal(): void
    {
        $this->rehearsalOfTheYes = null;
        $this->willRun = '';
    }
}
