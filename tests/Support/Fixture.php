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
        public string $replacing = '',
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
     * A file the analyser reads, planted where the rule is scoped to look.
     *
     * `$path` is a real path in this repository rather than one under the
     * fixture tree, because a rule that narrows by path cannot fire outside the
     * directory it narrows to — and a fixture that cannot make its rule fire
     * proves the opposite of what it was written for.
     *
     * The analyser is pointed at it directly, so it is read whatever the
     * configuration's own `paths` say.
     */
    public static function analyserInPlace(string $rule, string $path, string $code, string $marker): self
    {
        return new self($rule, Proof::AnalyserInPlace, $path, $code, $marker, basename($path, '.php'));
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

    /**
     * A change to a file this repository owns, put back afterwards.
     *
     * For the violation that is not a file: a second accessor on a type that
     * already exists, a name added to a closed set, an annotation that stops
     * handing on what it read. `$replacing` is found in the real file and
     * `$code` is put in its place, and the harness refuses a `$replacing` it
     * cannot find exactly once — a fixture that matched nothing would leave the
     * rule passing on an unedited tree, which is the vacuous green this whole
     * harness exists to make impossible.
     *
     * `$marker` and `$evidence` mean what they mean for a suite fixture, and
     * `$evidence` has no useful default here: the file is a real one, so its
     * name is not the fixture's.
     */
    public static function edit(
        string $rule,
        string $path,
        string $replacing,
        string $code,
        string $marker,
        string $evidence,
    ): self {
        return new self($rule, Proof::Edit, $path, $code, $marker, $evidence, $replacing);
    }

    /**
     * A rule whose own judgement is called with the violation.
     *
     * `$how` says what is handed to it, because there is no file to point at:
     * the whole of this fixture is the test that drives it, and a reader with
     * neither has to be told where to look.
     */
    public static function direct(string $rule, string $how): self
    {
        return new self($rule, Proof::Direct, '', '', $how, '');
    }

    /** A rule with no snippet that breaks it, and why. */
    public static function notDrivable(string $rule, string $reason): self
    {
        return new self($rule, Proof::NotDrivable, '', '', $reason, '');
    }
}
