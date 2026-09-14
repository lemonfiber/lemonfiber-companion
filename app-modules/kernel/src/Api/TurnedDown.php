<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Why a request was refused, in the words somebody gave.
 *
 * `D7-R7` says declining requires a reason and that the reason reaches the
 * requester; `N3-R7` says a refused request carries the reason that was given.
 * The wire has carried `refused: {at, reason}` all along and this app dropped
 * it, so a household member asking their operator *what happened to that film*
 * got the word `declined` and nothing else — which is the answer that sends
 * them to ask again in person, which is the whole thing the requirement exists
 * to prevent.
 *
 * **A refusal with no reason cannot be built.** `D7-R7` makes the reason part of
 * declining rather than an extra beside it, so a decline without one is a stack
 * that broke the rule rather than a row to render short. {@see RequestWasRefusedForNothing}
 * says so where the wire is read.
 *
 * **The moment is optional and is the requester's words, not this app's.**
 * Plenty of refusals carry no timestamp, and one that does carries it in the
 * stack's own frame — {@see Said} makes the same argument about a log line, and
 * for the same reason: re-rendering it here would have two people in different
 * places disagree about when something happened on one machine.
 */
final readonly class TurnedDown
{
    private function __construct(private string $reason, private ?string $at = null) {}

    /** Refused, with the reason and nothing about when. */
    public static function because(string $reason): self
    {
        return new self(self::said($reason));
    }

    /** Refused at a moment the stack recorded, with the reason. */
    public static function at(string $when, string $reason): self
    {
        return new self(self::said($reason), $when);
    }

    /** What they were told, which is what `N3-R7` is about. */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * Say when, or say that nobody recorded it.
     *
     * @template TThen of object
     * @template TUnstated of object
     *
     * @param  Closure(string): TThen $then
     * @param  Closure(): TUnstated   $unstated
     * @return TThen|TUnstated
     */
    public function when(Closure $then, Closure $unstated): object
    {
        return $this->at === null ? $unstated() : $then($this->at);
    }

    /** The reason, refused when there is none to give. */
    private static function said(string $reason): string
    {
        $given = trim($reason);

        if ($given === '') {
            throw RequestWasRefusedForNothing::andSomebodyIsWaitingToHearWhy();
        }

        return $given;
    }
}
