<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * How a rule is shown to refuse its own violation.
 *
 * The four that can be driven each need a different machine, and the fifth is
 * the honest answer for a rule whose enforcement is a number rather than a
 * check — a coverage floor has no single snippet that breaks it, and inventing
 * one would be the same pretending this whole harness exists to stop.
 */
enum Proof: string
{
    /** A file the analyser reads, expecting the rule's marker in the output. */
    case Analyser = 'analyser';

    /** A file the suite reads, expecting the named test to fail. */
    case Suite = 'suite';

    /**
     * A file the suite reads, in a run of its own.
     *
     * `->only()` is the one violation that changes what the rest of the run
     * does: Pest narrows to that single test and reports green, so every other
     * fixture in the same pass goes unexamined. Proving the rule that refuses it
     * therefore costs a second pass, which is the rule making its own case.
     */
    case IsolatedSuite = 'isolated-suite';

    /**
     * A change to a file this repository owns, put back afterwards.
     *
     * Some violations are not a file that can be dropped in beside the real
     * ones: a second accessor on a type that already exists, a `@param` that
     * stops handing on what it read, a name added to a closed set. Each is an
     * edit to one file, and for a long time each was answered with
     * `NotDrivable` and a paragraph — which is an honest answer to the wrong
     * question, because the harness could not edit rather than because nothing
     * could break the rule.
     *
     * What makes it safe is the sweep: the manifest records what a write
     * replaced, so an edited file goes back exactly as it was after a failure,
     * an exception or a kill, and the by-name pass undoes the replacement
     * directly for the run where the manifest is what went missing.
     */
    case Edit = 'edit';

    /** Nothing a snippet can break; the reason is recorded instead. */
    case NotDrivable = 'not-drivable';

    /**
     * Whether the suite is what reads this, and a failing test is the proof.
     *
     * `IsolatedSuite` is left out on purpose: it is read by the suite too, and
     * in a pass of its own, which is the whole of why it is a case apart.
     */
    public function readBySuite(): bool
    {
        return match ($this) {
            self::Suite, self::Edit => true,
            self::Analyser, self::IsolatedSuite, self::NotDrivable => false,
        };
    }
}
