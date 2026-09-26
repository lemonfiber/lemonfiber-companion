<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheHandoverDid;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\WhatTheHandoverShows;

/**
 * What handing a command over produced, as the fields a screen draws.
 *
 * `F2` — data in, view model out, so what an operator is told after an install
 * is stated in a test rather than arranged behind a port.
 *
 * **An install is reported by where the command stands, never as a success.**
 * The heading says what was asked for; whether it runs is the standing the
 * stack read afterwards and whether it was started, each its own line.
 */
final readonly class HowAHandoverReads
{
    /** The stack did it, and said what it did. */
    public function did(WhatTheHandoverDid $did): WhatTheHandoverShows
    {
        $installing = $did->did() === HandingOver::Install;

        return new WhatTheHandoverShows(
            headingSaid: $installing ? 'stacks.handed_over.heading.install' : 'stacks.handed_over.heading.remove',
            name: $did->name(),
            rehearsed: $did->wasRehearsed(),
            startedSaid: $installing ? $this->started($did) : '',
            standingSaid: $did->standing()->saidOnTheScreen(),
            writesToSaid: $installing ? $this->writesToSaid($did) : '',
            writesTo: $did->writesTo(
                there: AsText::of(...),
                unsaid: AsText::nothing(...),
            )->said,
            touched: $this->files($did),
            touchedSaid: $this->touchedSaid($did),
            touchedNothingSaid: $installing ? 'stacks.handed_over.touched_nothing.install' : 'stacks.handed_over.touched_nothing.remove',
            refused: '',
            metSaid: '',
        );
    }

    /** The stack answered and would not, in its own words. */
    public function refused(string $name, string $said): WhatTheHandoverShows
    {
        return $this->didNot($name, $said, '');
    }

    /** It never got an answer, and this is what the operator met. */
    public function met(string $name, Obstacle $why): WhatTheHandoverShows
    {
        return $this->didNot($name, '', $why->said());
    }

    /**
     * This device no longer holds a session for that stack, so nothing was sent.
     *
     * No reason beside it, because nothing was met: the screen goes to signing
     * in, which is the whole of what there is to say.
     */
    public function signedOut(string $name): WhatTheHandoverShows
    {
        return $this->didNot($name, '', '');
    }

    /** Neither arm of an act that did not happen has a standing, a file, or a start. */
    private function didNot(string $name, string $refused, string $metSaid): WhatTheHandoverShows
    {
        return new WhatTheHandoverShows(
            headingSaid: 'stacks.handed_over.heading.did_not',
            name: $name,
            rehearsed: false,
            startedSaid: '',
            standingSaid: '',
            writesToSaid: '',
            writesTo: '',
            touched: [],
            touchedSaid: '',
            touchedNothingSaid: '',
            refused: $refused,
            metSaid: $metSaid,
        );
    }

    /**
     * Every file, as a list a template can walk.
     *
     * Collected by hand rather than with `iterator_to_array`, for
     * `WorstFirst::over()`'s reason: the files are always a list, so a
     * `preserve_keys` argument could not change the answer.
     *
     * @return list<string>
     */
    private function files(WhatTheHandoverDid $did): array
    {
        $files = [];

        foreach ($did->touched() as $file) {
            $files[] = $file;
        }

        return $files;
    }

    /** Whether an install started the command, as the key that says so. */
    private function started(WhatTheHandoverDid $did): string
    {
        return $did->started() ? 'stacks.handed_over.started' : 'stacks.handed_over.not_started';
    }

    /** Where an install's words go, or that the stack did not say. */
    private function writesToSaid(WhatTheHandoverDid $did): string
    {
        return $did->writesTo(
            there: static fn(): AsText => AsText::of('stacks.handed_over.writes_to'),
            unsaid: static fn(): AsText => AsText::of('stacks.handed_over.writes_unsaid'),
        )->said;
    }

    /**
     * The key each file is listed under.
     *
     * A rehearsal names the files it would have touched and touched none, so
     * its files are said as what would happen rather than what did.
     */
    private function touchedSaid(WhatTheHandoverDid $did): string
    {
        return match (true) {
            $did->did() === HandingOver::Install && $did->wasRehearsed() => 'stacks.handed_over.would_touch.install',
            $did->did() === HandingOver::Install => 'stacks.handed_over.touched.install',
            $did->wasRehearsed() => 'stacks.handed_over.would_touch.remove',
            default => 'stacks.handed_over.touched.remove',
        };
    }
}
