<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * How a rule is shown to refuse its own violation.
 *
 * The three that can be driven each need a different machine, and the fourth is
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

    /** Nothing a snippet can break; the reason is recorded instead. */
    case NotDrivable = 'not-drivable';
}
