<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A refusal, in the form an operator can act on.
 *
 * This is what crosses a module boundary when something did not happen (C1).
 * It is deliberately not an exception: this application spends its life
 * talking to a machine that may be off, asleep, on another network or
 * mid-update, so unreachable is a normal Tuesday rather than an exceptional
 * one, and modelling it as a throw makes the common case the one the compiler
 * cannot see you forgot.
 *
 * The shape is the server's `Problem`, from
 * `lemonfiber:contract/web-api.contract.json`, minus the parts noted below. It
 * is not the wire type: the `sdk` adapter is the one module allowed to know
 * that, and it maps one to the other. What is preserved is the split the
 * server draws and every screen depends on — a summary an operator reads, a
 * meaning that says why it matters to them, and remedies phrased as things to
 * do.
 *
 * **Two wire fields are deliberately absent.** `detail` — the technical text,
 * available but never leading — and `cause`, the problem that produced this
 * one, are both optional on the wire, and C2 refuses a nullable return on
 * anything a module publishes. Carrying them honestly needs an absence type,
 * and which absence type is a design decision worth making when a screen
 * actually needs one rather than guessed at now. Until then this carries what
 * is always present, and the omission is named rather than silent.
 */
final readonly class Refusal
{
    private function __construct(
        private Code $code,
        private Severity $severity,
        private Standing $standing,
        private string $summary,
        private string $meaning,
        private Remedies $remedies,
    ) {}

    /**
     * The one place the strings a server sent become a refusal.
     *
     * A named constructor is where a primitive is permitted to cross into a
     * module (D2), and it is where they are checked: a summary or a meaning
     * that is blank renders as a screen with a heading and no sentence under
     * it, which reads to the operator as the application having broken rather
     * than as the server having said nothing.
     */
    public static function of(
        Code $code,
        Severity $severity,
        Standing $standing,
        string $summary,
        string $meaning,
        Remedies $remedies,
    ): self {
        $said = trim($summary);
        $means = trim($meaning);

        if ($said === '' || $means === '') {
            throw RefusalSaysNothing::about($code);
        }

        return new self($code, $severity, $standing, $said, $means, $remedies);
    }

    public function code(): Code
    {
        return $this->code;
    }

    public function severity(): Severity
    {
        return $this->severity;
    }

    public function standing(): Standing
    {
        return $this->standing;
    }

    /** What happened, in one plain sentence. */
    public function summary(): string
    {
        return $this->summary;
    }

    /** What it means for the operator. */
    public function meaning(): string
    {
        return $this->meaning;
    }

    public function remedies(): Remedies
    {
        return $this->remedies;
    }
}
