<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function count;

use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Sdk\Api\Abilities;
use Modules\Sdk\Api\CapabilitiesAreUnreadable;

/**
 * What each stack last said it can do, held in memory and never kept.
 *
 * One answer per stack, so two stacks that differ in what they support are
 * never answered for one another. Held for the life of the process, which the
 * runtime keeps across every screen, and let go of when the stack is removed,
 * when the operator asks again, and when a screen opens after a break; an
 * answer asked with a session that has since ended is never given to another.
 * While a screen is open it is never let go of by age, so an open screen never
 * stops to ask again.
 *
 * **Three ways asking ends.** The stack declares what it serves; it refuses
 * the request for the declaration as a path it has no endpoint at, which is a
 * stack older than the declaration; or it cannot be asked, and then every
 * action stays offered. A stack that did not answer at all has that silence
 * given to the request that asked, rather than the request sent to wait as
 * long again for the same silence. A version number is never read to decide
 * any of it.
 *
 * **Asking is the frame's one reading.** Where nothing is held, asking the
 * stack what it serves is the one reading the frame takes of it. A request it
 * does not serve is refused there, which sends nothing more; anything else
 * waits for the next frame ({@see TheReadingWaitsAFrame}), and so does a
 * button, whatever the stack said, so a request the same frame sends after
 * drawing it is never a second reading.
 *
 * **Mutable, because what is held is**, for {@see TheStreamsHeld}'s reason:
 * it is what one screen asked and the next screen draws.
 */
final class WhatEachStackOffers
{
    /** @var array<string, WhatAStackDeclared> each stack's answer, by its stored identifier */
    private array $held = [];

    /**
     * Whether this stack serves the request at this path to this session.
     *
     * Answered from what is held for this session, and asked of the stack
     * through this client where nothing is: the one read sent without asking
     * first, because it is the answer the asking is for.
     *
     * @throws Unreachable where the stack did not answer the asking
     * @throws TheReadingWaitsAFrame where the stack had to be asked and serves the path
     */
    public function at(Stack $stack, Session $session, Instant $now, Client $client, Ability $path): WhetherItIsOffered
    {
        $held = $this->heldFor($stack, $session);

        if ($held instanceof WhatAStackDeclared) {
            return $held->at($path);
        }

        $offered = $this->askedNow($stack, $session, $now, $client)->at($path);

        return $offered->offersAnAction()
            ? throw TheReadingWaitsAFrame::becauseTheStackWasAskedWhatItServes()
            : $offered;
    }

    /**
     * Whether this stack offers the action at this path to this session, for its button.
     *
     * Answered from what is held for this session. Where nothing is, the stack
     * is asked, which is the frame's one reading of it, and the button is
     * drawn on the next frame whatever the answer.
     *
     * @throws TheReadingWaitsAFrame where the stack had to be asked
     */
    public function forAButton(Stack $stack, Session $session, Instant $now, Client $client, Ability $path): WhetherItIsOffered
    {
        $held = $this->heldFor($stack, $session);

        if ($held instanceof WhatAStackDeclared) {
            return $held->at($path);
        }

        try {
            $this->askedNow($stack, $session, $now, $client);
        } catch (Unreachable) {
            // Held as a stack that could not be asked; its reading says why.
        }

        throw TheReadingWaitsAFrame::becauseTheStackWasAskedWhatItServes();
    }

    /** Let go of what one stack said, and say whether anything was held. */
    public function letGoOf(StackId $stack): bool
    {
        $held = $this->holds($stack);

        unset($this->held[$stack->stored()]);

        return $held;
    }

    /** Let go of what every stack said longer ago than a break, and say how many. */
    public function letGoOfWhatIsOlderThanABreak(Instant $now): int
    {
        $old = array_keys(array_filter(
            $this->held,
            static fn(WhatAStackDeclared $declared): bool => $declared->isOlderThanABreakAt($now),
        ));

        foreach ($old as $which) {
            unset($this->held[$which]);
        }

        return count($old);
    }

    /** Whether anything is held for this stack. */
    public function holds(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->held);
    }

    /** What this stack said to this session, where anything is held. */
    private function heldFor(Stack $stack, Session $session): ?WhatAStackDeclared
    {
        $which = $stack->id()->stored();

        if (! array_key_exists($which, $this->held) || ! $this->held[$which]->isFor($session)) {
            return null;
        }

        return $this->held[$which];
    }

    /**
     * What the stack says now, held, however asking it ends.
     *
     * @throws Unreachable where the stack did not answer, held as a stack that could not be asked
     */
    private function askedNow(Stack $stack, Session $session, Instant $now, Client $client): WhatAStackDeclared
    {
        $which = $stack->id()->stored();

        try {
            return $this->held[$which] = $this->asked($stack, $session, $now, $client);
        } catch (Unreachable $why) {
            $this->held[$which] = WhatAStackDeclared::unasked($session, $now);

            throw $why;
        }
    }

    /**
     * What the stack says now, however asking it ends but in silence.
     *
     * @throws Unreachable where the stack did not answer
     */
    private function asked(Stack $stack, Session $session, Instant $now, Client $client): WhatAStackDeclared
    {
        try {
            return WhatAStackDeclared::declared(Abilities::in($client->read(Api::CAPABILITIES_ENDPOINT), $stack->id()), $session, $now);
        } catch (RequestFailed $why) {
            return $why->code() === RefusalCode::NoEndpoint
                ? WhatAStackDeclared::tooOldToSay($session, $now)
                : WhatAStackDeclared::unasked($session, $now);
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|EnvelopeIsNotRead|CapabilitiesAreUnreadable) {
            return WhatAStackDeclared::unasked($session, $now);
        }
    }
}
