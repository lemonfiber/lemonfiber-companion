<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What an operator decided about one thing the household asked for.
 *
 * {@see AgreedTo}'s argument applied to a request. `N2-R11` has a waiting
 * request be approvable and refusable from the app, and the way a decision goes
 * to the wrong subject is never a deliberate choice: a template draws a row,
 * the button is right there, and a handler passes its argument straight to the
 * port. So {@see Wanting::decided()} takes one of these, and the only ways to
 * make one name the request together with what was decided about it.
 *
 * **Two constructors, because approving and declining are different acts.** Not
 * one taking a nullable reason (`C2`): `D7-R7` makes the reason part of
 * declining rather than something beside it, so a refusal that could be built
 * without one is a refusal this app could send half of. *Declined*, with no
 * reason, is exactly the screen that sends somebody to ask their operator in
 * person — which is the thing the requirement exists to prevent.
 *
 * **The reason is read through a pair of closures**, the way
 * {@see Wanted::refusal()} is read: an accessor returning an empty string for
 * an approval would be a caller asking *was there a reason* by comparing
 * against a blank, and a blank is also what a stack sending nothing produces.
 */
final readonly class Decided
{
    private function __construct(
        private RequestId $request,
        private WhatWasDecided $decided,
        private string $because,
    ) {}

    /** The operator approved it, and the person who asked gets what they asked for. */
    public static function toApprove(RequestId $request): self
    {
        return new self($request, WhatWasDecided::Approve, '');
    }

    /**
     * The operator turned it down, and this is what they are owed instead.
     *
     * The reason is refused blank here rather than at the screen, so no road to
     * this value can produce a refusal with nothing on it (`D7-R7`).
     */
    public static function toDecline(RequestId $request, string $because): self
    {
        $said = trim($because);

        if ($said === '') {
            throw RequestWasRefusedForNothing::andSomebodyIsWaitingToHearWhy();
        }

        return new self($request, WhatWasDecided::Decline, $said);
    }

    /** Which request this is about. */
    public function about(): RequestId
    {
        return $this->request;
    }

    /** What the stack is asked for, which is the last segment of the action's path. */
    public function asked(): string
    {
        return $this->decided->asked();
    }

    /**
     * What was said about it, where anything was.
     *
     * @template TWas of object
     * @template TWasNot of object
     *
     * @param Closure(string): TWas $was
     * @param Closure(): TWasNot   $wasNot
     *
     * @return TWas|TWasNot
     */
    public function why(Closure $was, Closure $wasNot): object
    {
        return $this->because === '' ? $wasNot() : $was($this->because);
    }
}
