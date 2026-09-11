<?php

declare(strict_types=1);

namespace Tests\Support;

use function basename;

/**
 * The smallest thing that a rule must refuse.
 *
 * A fixture is written where the rule would see it, the machine that enforces
 * the rule is run once over everything, and the fixture is removed. What the
 * harness asserts is that the rule reported — because a rule that exists,
 * carries its identifier and reports nothing is indistinguishable from a rule
 * that works, right up until something ships.
 */
final readonly class Fixture
{
    private function __construct(
        public string $rule,
        public Proof $proof,
        public string $path,
        public string $code,
        public string $marker,
        public string $evidence,
    ) {}

    /**
     * A file the analyser reads.
     *
     * `$marker` is what must appear in the analyser's output for this file —
     * the rule's identifier where our own rules carry one, and the vendor
     * rule's identifier where the enforcement is somebody else's.
     */
    public static function analyser(string $rule, string $path, string $code, string $marker): self
    {
        return new self($rule, Proof::Analyser, $path, $code, $marker, basename($path, '.php'));
    }

    /**
     * A file the test suite reads.
     *
     * `$marker` is the description of the test that must fail — matched as a
     * substring, so a rule generated per module can be named by its stem.
     *
     * `$evidence` is what that test must say, so a rule cannot pass on another
     * fixture's violation. It defaults to the fixture's class name, which is
     * what a Pest expectation over files reports. A boundary expectation names
     * namespaces rather than files, so those fixtures give the namespace.
     */
    public static function suite(string $rule, string $path, string $code, string $marker, string $evidence = ''): self
    {
        return new self(
            $rule,
            Proof::Suite,
            $path,
            $code,
            $marker,
            $evidence === '' ? basename($path, '.php') : $evidence,
        );
    }

    /**
     * A file the suite reads, in a pass of its own.
     *
     * For a violation that changes what the rest of the run does, and would
     * otherwise hide every other fixture in the same pass.
     */
    public static function isolatedSuite(string $rule, string $path, string $code, string $marker, string $evidence = ''): self
    {
        return new self(
            $rule,
            Proof::IsolatedSuite,
            $path,
            $code,
            $marker,
            $evidence === '' ? basename($path, '.php') : $evidence,
        );
    }

    /** A rule with no snippet that breaks it, and why. */
    public static function notDrivable(string $rule, string $reason): self
    {
        return new self($rule, Proof::NotDrivable, '', '', $reason, '');
    }
}
