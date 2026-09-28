<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatAReturnFinds;
use Modules\Kernel\Api\WorkLeftRunning;

use function sprintf;

/**
 * The handle of work left running on each stack, kept in the platform's own store.
 *
 * **The keychain rather than a file, for {@see PlatformStacks}' reason.** A
 * handle is not a secret, and it is still a note that this device started
 * something on a machine the same store holds the address of. A file in the
 * app's sandbox is readable by a backup, by a device that has been rooted, and
 * by whatever a restore puts it back onto, and this package offers no third
 * place.
 *
 * **A key per stack and kind**, as {@see PlatformKeychain} keeps a session per
 * stack and unlike the one record {@see PlatformStandings} keeps. Nothing reads
 * every handle at once: a screen asks after the one it follows, so one key each
 * is what lets a handle be written and let go of without reading, rewriting and
 * risking every other.
 *
 * **A shape number, because everything retained carries one**, and a value in
 * a shape this build does not recognise is discarded rather than interpreted.
 * Discarding costs the operator one thing: the screen opens offering to start
 * the work rather than following it, and the work itself goes on untouched.
 * Following a handle read out of a value nobody in this build wrote would ask a
 * stack about work nobody here started.
 */
final readonly class PlatformWorkLeftRunning implements WorkLeftRunning
{
    /** What every key here is prefixed with, so nothing else in the store collides. */
    private const string UNDER = 'lemonfiber.left-running';

    /** The shape this build writes, and the only one it reads. */
    private const int SHAPE = 1;

    /** The field of a stored value that says which shape it was written in. */
    private const string SHAPE_UNDER = 'shape';

    /** The field of a stored value that holds the handle. */
    private const string JOB_UNDER = 'job';

    public function __construct(private Keeps $store) {}

    public function whatWasLeft(StackId $stack, KindOfWork $work): WhatAReturnFinds
    {
        return $this->store->read($this->keyFor($stack, $work))->either(
            found: fn(string $written): WhatAReturnFinds => $this->read($written),
            nothing: static fn(): WhatAReturnFinds => WhatAReturnFinds::nothing(),
            // A store that cannot be asked has nothing to pick up as far as
            // this question goes. The work is on the stack rather than in the
            // store, so the cost is a screen that offers to start it rather
            // than follow it — and both refusals cost that, so they are one arm.
            refused: static fn(): WhatAReturnFinds => WhatAReturnFinds::nothing(),
        );
    }

    public function remember(StackId $stack, KindOfWork $work, Job $job): WhatAReturnFinds
    {
        $written = json_encode([self::SHAPE_UNDER => self::SHAPE, self::JOB_UNDER => $job->shown()]);

        // A handle is the stack's text and `Job` asks only that it not be
        // blank, so a name that is not valid text reaches `json_encode`, which
        // answers false. Nothing is kept, and that is what a return will find.
        if ($written === false) {
            return WhatAReturnFinds::nothing();
        }

        return $this->store->keep($this->keyFor($stack, $work), $written, WhenAValueMayBeRead::WhileUnlocked)->either(
            done: static fn(): WhatAReturnFinds => WhatAReturnFinds::theJob($job),
            refused: static fn(): WhatAReturnFinds => WhatAReturnFinds::nothing(),
        );
    }

    public function forget(StackId $stack, KindOfWork $work): WhatAReturnFinds
    {
        // The store's answer is deliberately not read, for the reason the port
        // gives: while a store refuses to forget, it reads nothing back either.
        $this->store->forget($this->keyFor($stack, $work));

        return WhatAReturnFinds::nothing();
    }

    /**
     * The handle a stored value holds, where it is a value this build wrote.
     *
     * Every refusal answers nothing rather than raising: this is read as a
     * screen opens, and the honest thing to show there is the screen offering to
     * start the work, which says what to do next.
     */
    private function read(string $written): WhatAReturnFinds
    {
        $named = $this->nameIn(json_decode($written, associative: true));

        if ($named === null) {
            return WhatAReturnFinds::nothing();
        }

        // `Job` refuses a blank name, and that refusal is the one rule about a
        // handle — asked of the type rather than written a second time here,
        // where the two could come to disagree.
        try {
            return WhatAReturnFinds::theJob(Job::named($named));
        } catch (JobHasNoName) {
            return WhatAReturnFinds::nothing();
        }
    }

    /**
     * The name a decoded value holds, where it is in the shape this build writes.
     *
     * This decides whether the envelope is ours; {@see read()} decides whether
     * what is inside it is a handle.
     */
    private function nameIn(mixed $found): ?string
    {
        if (! is_array($found) || ! array_key_exists(self::SHAPE_UNDER, $found) || $found[self::SHAPE_UNDER] !== self::SHAPE) {
            return null;
        }

        if (! array_key_exists(self::JOB_UNDER, $found) || ! is_string($found[self::JOB_UNDER])) {
            return null;
        }

        return $found[self::JOB_UNDER];
    }

    /** One key per stack and kind, so neither two stacks nor two kinds of work share a handle. */
    private function keyFor(StackId $stack, KindOfWork $work): string
    {
        return sprintf('%s.%s.%s', self::UNDER, $work->value, $stack->stored());
    }
}
