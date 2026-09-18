<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One thing a stack runs, and what an operator needs before touching it.
 *
 * The app offers start, stop and restart by service, and a
 * button is only offerable if the row above it says enough to decide with. The
 * contract sends `{criticality, depends_on, exit?, id, name, profile, state}`
 * and four of those are that decision: **what it is called**, **where it
 * stands**, **how much it matters**, and **what else is leaning on it**.
 *
 * **Named `Daemon` rather than for the word the contract uses**, because `H1`
 * refuses the suffix `Service` — a name that permits anything — and because
 * this is precisely what these are: long-running processes a stack supervises.
 * The word an operator reads is the catalogue's and is *service*; this is the
 * type's name and nobody sees it.
 *
 * **The name and the id are different things and both are kept.** The id is
 * what an action is asked for by; the name is what an operator reads. A screen
 * holding only the name cannot act, and one holding only the id shows somebody
 * `sonarr-4` where *Sonarr* belongs — {@see ServiceId} carries the first and is
 * the same value a log window is read for, which is what lets a row here send
 * somebody to that service's scrollback.
 *
 * **What leans on it is carried, because stopping is not a local act.**
 * A disruptive action states what it disturbs, and the honest
 * answer to *what does stopping this disturb* is the list of things that stop
 * with it. A screen offering a stop without it would be asking somebody to
 * confirm something they were not told.
 */
final readonly class Daemon
{
    private function __construct(
        private ServiceId $id,
        private string $name,
        private Form $profile,
        private HowAServiceRuns $runs,
        private HowMuchItMatters $matters,
        private WhatLeansOnIt $leaning,
        private ?int $exit = null,
    ) {}

    /**
     * The one place a service on the wire becomes one this app can show.
     *
     * A blank name is refused for {@see Repair::offered()}'s reason: it renders
     * as a row with a button and no label, which is the worst version of this
     * screen. The id is refused by {@see ServiceId} itself, one layer down.
     */
    public static function called(
        string $name,
        ServiceId $id,
        Form $profile,
        HowAServiceRuns $runs,
        HowMuchItMatters $matters,
        WhatLeansOnIt $leaning,
    ): self {
        $shown = trim($name);

        if ($shown === '') {
            throw ServiceIsUnnamed::whereOneWasExpected();
        }

        return new self($id, $shown, $profile, $runs, $matters, $leaning);
    }

    /**
     * The same, for a service that ended with a code.
     *
     * A second named constructor rather than a nullable seventh parameter,
     * which is `C2`'s cure and {@see Wanted::turnedDown()}'s shape: *it exited
     * with a code* and *nothing said how it ended* are different facts, and a
     * caller cannot reach one while meaning the other. It is static rather than
     * a method on an instance because `D2` lets a primitive cross a module
     * boundary in exactly one place, and this is that place.
     */
    public static function thatExited(
        string $name,
        ServiceId $id,
        Form $profile,
        HowAServiceRuns $runs,
        HowMuchItMatters $matters,
        WhatLeansOnIt $leaning,
        int $code,
    ): self {
        $was = self::called($name, $id, $profile, $runs, $matters, $leaning);

        return new self($was->id, $was->name, $was->profile, $was->runs, $was->matters, $was->leaning, $code);
    }

    /** What an action is asked for by, and what its scrollback is read for. */
    public function id(): ServiceId
    {
        return $this->id;
    }

    /** What an operator reads. */
    public function name(): string
    {
        return $this->name;
    }

    /** Which form it belongs to, for reading a stack by form. */
    public function profile(): Form
    {
        return $this->profile;
    }

    /** Where it stands right now. */
    public function runs(): HowAServiceRuns
    {
        return $this->runs;
    }

    /** What it would cost if that went wrong. */
    public function matters(): HowMuchItMatters
    {
        return $this->matters;
    }

    /** What stopping this would take with it. */
    public function whatLeansOnIt(): WhatLeansOnIt
    {
        return $this->leaning;
    }

    /**
     * Say the code it exited on, or say that nothing did.
     *
     * Two arms rather than a nullable getter, for `C2`'s reason: a screen handed
     * a null would print an empty column where a number belongs, and an
     * operator would read that as *it exited with nothing*.
     *
     * The code is machine data on the way through, shown as it arrived — a
     * sentence composed around it here is one no translator can reach (`L1`),
     * which is {@see Said}'s argument about a log line's timestamp.
     *
     * @template TCode of object
     * @template TUnstated of object
     *
     * @param  Closure(int): TCode  $said
     * @param  Closure(): TUnstated $unstated
     * @return TCode|TUnstated
     */
    public function exit(Closure $said, Closure $unstated): object
    {
        return $this->exit === null ? $unstated() : $said($this->exit);
    }
}
