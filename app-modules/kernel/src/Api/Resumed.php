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
 * **Whose it is travels with it**, because the first thing a resumed session decides
 * is which application the person holding it is given. A screen that does not turn on
 * that declares the one parameter it always declared and is unchanged; the one that
 * does declares the second.
 *
 * `notHeld()` names the operator, which costs nothing and is read by nobody: there is
 * no session, so no arm receives it. The field is not nullable because a null would be
 * a third state for a question with two answers.
 *
 * That is also why a refusal to read is not an error here. A keychain that will
 * not open is a keychain with no session in it as far as this question goes —
 * treating it as a fault would put a screen in front of the operator that says
 * something went wrong, where the honest thing is a password field.
 */
final readonly class Resumed
{
    private function __construct(private ?Session $session, private Whose $whose) {}

    /** There is a session for this stack, this is it, and this is whose it is. */
    public static function with(Session $session, Whose $whose): self
    {
        return new self($session, $whose);
    }

    /** There is not, for whatever reason, and whoever it is signs in again. */
    public static function notHeld(): self
    {
        return new self(null, Whose::theOperator());
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
     * @param Closure(Session, Whose): THeld $held
     * @param Closure(): TNotHeld     $notHeld
     *
     * @return THeld|TNotHeld
     */
    public function either(Closure $held, Closure $notHeld): object
    {
        return $this->session instanceof Session ? $held($this->session, $this->whose) : $notHeld();
    }

    /**
     * Whose session this device holds, without handing the session over.
     *
     * The same question {@see either()} answers, asked by a caller that wants the
     * subject and not the secret. Deciding which application somebody is given is
     * exactly that caller: it turns on who signed in and never speaks to a stack,
     * and {@see either()} would make it accept a session to reach the name beside
     * one. A screen holding a session it has no use for is the thing this kernel
     * spends most of its shape preventing, and the shortest way to keep a session
     * out of one is to have the answer it wants not contain one.
     *
     * **Three states through two arms.** Nobody is signed in, the operator is, or a
     * member is — and the second and third are one arm here because {@see Whose} is
     * the type that tells them apart, so a third arm would be this fold answering a
     * question that one already answers. What cannot be collapsed is the first: a
     * device with no session is not a device the operator is signed into, and
     * {@see notHeld()} naming the operator is why reading the subject off a bare
     * accessor would say it was.
     *
     * @template TNobody of object
     * @template TTheirs of object
     *
     * @param Closure(): TNobody       $nobody
     * @param Closure(Whose): TTheirs  $theirs
     *
     * @return TNobody|TTheirs
     */
    public function whoseItIs(Closure $nobody, Closure $theirs): object
    {
        return $this->session instanceof Session ? $theirs($this->whose) : $nobody();
    }
}
