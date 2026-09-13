<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing a check established, and how it turned out.
 *
 * The four fields every finding on the wire carries: which check, which
 * family it belongs to, a one-line summary of what was checked, and the
 * conclusion.
 *
 * **Two more the engine sets after the run**, and they are set the way the
 * engine sets them: a finding is made complete and then said to be about a
 * service, or said to be explained by another check. A check is independent by
 * construction and cannot see what any other found, so neither of those is the
 * check's own business — which is why they are not arguments to
 * {@see self::of()} and why a finding that names neither is the ordinary case
 * rather than an incomplete one.
 *
 * **`said` — what the service itself last said — is still dropped.** It is the
 * one field here with no honest place to go: log output of a length nobody can
 * predict, and this surface has five components and no way to fold anything
 * away. Putting it inline would push every following row off the screen, and
 * cutting it to fit would be this app editing the machine's words. It waits for
 * somewhere to put it, which is a different thing from waiting for a screen —
 * the screen exists.
 */
final readonly class Finding
{
    private function __construct(
        private Check $check,
        private Category $category,
        private string $title,
        private Conclusion $conclusion,
        private WhatTheCheckSaid $said,
        private AboutWhat $about,
        private Because $because,
    ) {}

    /**
     * The one place a report's row becomes a finding.
     *
     * A named constructor is where a primitive is permitted to cross into a
     * module (D2), and it is where the title is checked: a row with a blank
     * title renders as an empty line in a list the operator is scanning for
     * the thing that is wrong.
     */
    public static function of(
        Check $check,
        Category $category,
        string $title,
        Conclusion $conclusion,
        WhatTheCheckSaid $said,
    ): self {
        $trimmed = trim($title);

        if ($trimmed === '') {
            throw FindingHasNoTitle::about($check);
        }

        return new self(
            $check,
            $category,
            $trimmed,
            $conclusion,
            $said,
            AboutWhat::theMachine(),
            Because::nothingElse(),
        );
    }

    /**
     * The same finding, said to be about a particular service.
     *
     * Written as the engine writes it — `Finding::in_category(..).about("x")` —
     * rather than as a sixth argument, because that is what it is: a fact added
     * once the run knows which service a check turned out to be about, not
     * something the check itself established.
     *
     * Takes the value rather than the name, because `D2` keeps a primitive out
     * of a published signature that is not a named constructor — and because
     * the two arms are then visible where the finding is built, rather than
     * hidden behind a string that might be empty.
     */
    public function about(AboutWhat $about): self
    {
        return new self(
            $this->check,
            $this->category,
            $this->title,
            $this->conclusion,
            $this->said,
            $about,
            $this->because,
        );
    }

    /**
     * The same finding, said to be explained by another check's.
     *
     * Set after the run for the same reason and in the same shape. The check
     * named is one of the run's own; a report naming a check it does not
     * contain is the engine's fault and is shown as the identifier rather than
     * hidden, because an operator can quote an identifier to somebody.
     */
    public function because(Because $because): self
    {
        return new self(
            $this->check,
            $this->category,
            $this->title,
            $this->conclusion,
            $this->said,
            $this->about,
            $because,
        );
    }

    public function check(): Check
    {
        return $this->check;
    }

    public function category(): Category
    {
        return $this->category;
    }

    /** The one-line summary of what was checked. */
    public function title(): string
    {
        return $this->title;
    }

    public function conclusion(): Conclusion
    {
        return $this->conclusion;
    }

    /**
     * What the core said about it, in the core's own words (`N2-R3`).
     *
     * A `WhatTheCheckSaid` rather than three readers, so a screen cannot ask
     * for the meaning without having established that there is one. The words
     * arrived on the wire and were dropped for as long as this type had nowhere
     * to put them.
     */
    public function said(): WhatTheCheckSaid
    {
        return $this->said;
    }

    /**
     * Which service this is about, where it is about one.
     *
     * Beside the title rather than inside it: the same check runs against
     * whichever service fills a role, so a title naming one would be wrong on
     * the next machine — and an operator with nineteen services needs the name.
     */
    public function whatItIsAbout(): AboutWhat
    {
        return $this->about;
    }

    /**
     * What explains this one, where something in the same run does.
     *
     * The difference between an operator reading five things wrong with their
     * machine and reading one broken thing that four services noticed.
     */
    public function whatExplainsIt(): Because
    {
        return $this->because;
    }
}
