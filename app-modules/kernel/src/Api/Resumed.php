<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether a stack still has a session on this device, and which.
 *
 * The answer to *do I have to ask for the password again*, and a value rather
 * than a nullable {@see Session} because a null is a thing a caller forgets to
 * check — and the branch it forgets is the one that carries a credential
 * somewhere it has no business being. `either()` makes both arms mandatory,
 * which is {@see Kept}'s shape and {@see Admitted}'s for the same reason.
 *
 * **Nothing is said about *why* there is no session.** A device that has never
 * signed into this stack, one whose store would not open, and one whose session
 * the operator ended are one situation to whoever is looking at the screen:
 * they are being asked for the password. The distinctions matter when a
 * session is being *kept*, because the remedies differ; when resuming, there is
 * one remedy and it is the sign-in screen.
 *
 * That is also why a refusal to read is not an error here. A keychain that will
 * not open is a keychain with no session in it as far as this question goes —
 * treating it as a fault would put a screen in front of the operator that says
 * something went wrong, where the honest thing is a password field.
 */
final readonly class Resumed
{
    private function __construct(private ?Session $session) {}

    /** There is a session for this stack, and this is it. */
    public static function with(Session $session): self
    {
        return new self($session);
    }

    /** There is not, for whatever reason, and the operator signs in again. */
    public static function notHeld(): self
    {
        return new self(null);
    }

    /**
     * Take one arm or the other, saying what happens either way.
     *
     * Generic in both arms, which is {@see Kept::either()}'s shape and carries
     * its weight rather than its style: a caller builds its own type in each
     * arm and gets that type back, so reading the answer needs no narrowing and
     * no cast at a call site the analyser would otherwise have to be told about.
     *
     * @template THeld of object
     * @template TNotHeld of object
     *
     * @param Closure(Session): THeld $held
     * @param Closure(): TNotHeld     $notHeld
     *
     * @return THeld|TNotHeld
     */
    public function either(Closure $held, Closure $notHeld): object
    {
        return $this->session instanceof Session ? $held($this->session) : $notHeld();
    }
}
