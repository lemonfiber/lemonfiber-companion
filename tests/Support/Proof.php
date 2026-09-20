<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * How a rule is shown to refuse its own violation.
 *
 * The five that can be driven each need a different machine, and the sixth is
 * the honest answer for a rule whose enforcement is a number rather than a
 * check — a coverage floor has no single snippet that breaks it, and inventing
 * one would be the same pretending this whole harness exists to stop.
 */
enum Proof: string
{
    /** A file the analyser reads, expecting the rule's marker in the output. */
    case Analyser = 'analyser';

    /**
     * A file the analyser reads, planted where the rule is scoped to look.
     *
     * For a rule that narrows by path. The fixture tree sits outside every
     * source directory on purpose — that is what makes a rule scoped to
     * "everywhere but the adapters" apply to a fixture — and the same fact
     * makes it the one place a rule scoped *to* a directory can never fire. A
     * fixture planted there reports nothing, which reads exactly like a rule
     * that does not work, so the register would be claiming ground no run had
     * tested.
     *
     * So this one goes into the real tree, at the path the rule is looking at,
     * and comes back out by the same manifest that puts an edited file back.
     * Every such path sits in a directory called `Fixtures`, which `.gitignore`
     * and `pint.json` already exclude and which `phpstan.neon` deliberately
     * does not — the analyser has to be able to read it, or there is nothing
     * gained by planting it there.
     */
    case AnalyserInPlace = 'analyser-in-place';

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

    /**
     * The rule's own judgement, called with the violation instead of finding it.
     *
     * For a rule whose subject is this harness. The violation is a state of the
     * run doing the reading — a rule documented with nothing planted under it, a
     * root that answers with somewhere else — so there is no file to drop in
     * beside the others, because the run that read it would be this run.
     *
     * `NotDrivable` was the answer here for a long time, and it was wrong for
     * exactly the reason it was wrong before `Edit` existed: what could not be
     * done was the planting, not the breaking. Split the judgement from the
     * reading and it can be handed the violation directly, on every run, which
     * is the whole of what a planted file buys.
     */
    case Direct = 'direct';

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
            self::Analyser, self::AnalyserInPlace, self::IsolatedSuite, self::Direct, self::NotDrivable => false,
        };
    }

    /**
     * Whether this fixture is a file of its own, which the sweep may delete by
     * name.
     *
     * The distinction the sweep rests on, and the reason it is a question about
     * the kind rather than a list at the call site. A fixture that is a whole
     * file sits at a path nothing else uses, so deleting it is safe even on the
     * run where the manifest is itself what went missing. An edited file is one
     * this repository owns, and deleting that is the one outcome worse than
     * leaving it edited.
     */
    public function isAFileOfItsOwn(): bool
    {
        return match ($this) {
            self::Analyser, self::AnalyserInPlace, self::Suite, self::IsolatedSuite => true,
            self::Edit, self::Direct, self::NotDrivable => false,
        };
    }

    /**
     * Whether the analyser is what reads this, and a message it reported is the
     * proof.
     *
     * Both places a fixture can be planted answer yes. What differs between
     * them is where the file goes, which the harness answers in one place; what
     * reads it is this question, and it has the same answer for both. A reader
     * that asked for `Analyser` by name instead would have gone quiet on the
     * in-place kind the day it arrived — and gone quiet is the failure every
     * rule here exists to refuse.
     */
    public function readByAnalyser(): bool
    {
        return match ($this) {
            self::Analyser, self::AnalyserInPlace => true,
            self::Suite, self::IsolatedSuite, self::Edit, self::Direct, self::NotDrivable => false,
        };
    }
}
